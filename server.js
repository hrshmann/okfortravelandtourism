/*
 * OK FOR TRAVEL AND TOURISM CO - website + travel engine server (Node.js edition).
 *
 * A drop-in port of server.py: same routes, same JSON, same data/ files.
 * Zero dependencies: runs on the Node.js 18+ standard library.
 *
 *     node server.js            -> http://localhost:8000
 *     node server.js 9000       -> custom port
 *
 * Configuration lives in a `.env` file next to this script (see .env.example).
 * On Hostinger, set server.js as the entry file; the PORT it provides is used automatically.
 */

'use strict';

const crypto = require('crypto');
const fs = require('fs');
const http = require('http');
const path = require('path');

const ROOT = __dirname;
const PUBLIC = path.join(ROOT, 'public');
const DATA = path.join(ROOT, 'data');


// --------------------------------------------------------------------------- config
const REAL_ENV = { ...process.env };

function loadEnv() {
  const envFile = path.join(ROOT, '.env');
  if (!fs.existsSync(envFile)) return;
  for (let line of fs.readFileSync(envFile, 'utf8').split(/\r\n|\r|\n/)) {
    line = line.trim();
    if (!line || line.startsWith('#') || !line.includes('=')) continue;
    const at = line.indexOf('=');
    const key = line.slice(0, at).trim();
    const value = line.slice(at + 1).trim().replace(/^"+|"+$/g, '').replace(/^'+|'+$/g, '');
    if (!(key in process.env)) process.env[key] = value;  // real environment variables win
  }
}

loadEnv();
const env = (key, fallback) => (key in process.env ? process.env[key] : fallback);
const ADMIN_PASSWORD = env('ADMIN_PASSWORD', 'admin123');
const FLIGHT_PROVIDER = env('FLIGHT_PROVIDER', 'auto').toLowerCase();
const DUFFEL_TOKEN = env('DUFFEL_ACCESS_TOKEN', '');
const TRAVELPAYOUTS_TOKEN = env('TRAVELPAYOUTS_TOKEN', '');
const TRAVELPAYOUTS_MARKER = env('TRAVELPAYOUTS_MARKER', '');


// --------------------------------------------------------------------------- storage
// Node runs one request at a time through these synchronous calls, so no lock is needed.
function readJson(name, fallback) {
  const file = path.join(DATA, name);
  if (!fs.existsSync(file)) return fallback;
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function writeJson(name, payload) {
  fs.mkdirSync(DATA, { recursive: true });
  const tmp = path.join(DATA, name + '.tmp');
  fs.writeFileSync(tmp, JSON.stringify(payload, null, 2), 'utf8');
  fs.renameSync(tmp, path.join(DATA, name));
}


// --------------------------------------------------------------------------- python helpers
// Small pieces of Python semantics, so responses match server.py value for value.
function pyTruthy(v) {
  if (v === null || v === undefined || v === false || v === 0 || v === '') return false;
  if (Array.isArray(v)) return v.length > 0;
  if (typeof v === 'object') return Object.keys(v).length > 0;
  return true;
}

const pyOr = (a, b) => (pyTruthy(a) ? a : b);
const pyGet = (obj, key, fallback = null) =>
  (obj !== null && typeof obj === 'object' && !Array.isArray(obj) && key in obj ? obj[key] : fallback);

function pyNeed(obj, key) {
  if (obj === null || typeof obj !== 'object' || !(key in obj)) throw new Error(`KeyError: '${key}'`);
  return obj[key];
}

function pyFloat(v) {
  if (typeof v === 'number') return v;
  if (typeof v === 'string' && v.trim() !== '' && Number.isFinite(Number(v.trim()))) return Number(v.trim());
  throw new Error('could not convert to float');
}

function pyReprStr(s) {
  const quote = s.includes("'") && !s.includes('"') ? '"' : "'";
  const body = s.replace(/[\\\n\r\t\x00-\x1f\x7f]/g, (c) => ({ '\\': '\\\\', '\n': '\\n', '\r': '\\r', '\t': '\\t' }[c]
    || '\\x' + c.charCodeAt(0).toString(16).padStart(2, '0')));
  return quote + body.split(quote).join('\\' + quote) + quote;
}

// str(value) for JSON values; containers use repr() like Python does.
function pyStr(v, repr = false) {
  if (typeof v === 'string') return repr ? pyReprStr(v) : v;
  if (v === null || v === undefined) return 'None';
  if (typeof v === 'boolean') return v ? 'True' : 'False';
  if (typeof v === 'number') return String(v);
  if (Array.isArray(v)) return '[' + v.map((x) => pyStr(x, true)).join(', ') + ']';
  return '{' + Object.entries(v).map(([k, x]) => pyReprStr(k) + ': ' + pyStr(x, true)).join(', ') + '}';
}

const pySlice = (s, n) => Array.from(s).slice(0, n).join('');
const pyHex = (length) => crypto.randomBytes(8).toString('hex').slice(0, length);  // uuid.uuid4().hex[:length]

// datetime.fromisoformat() (Python 3.10 formats). Wall-clock time is kept as UTC
// milliseconds plus spare microseconds, so adding minutes never hits a DST jump.
function pyFromIsoformat(s) {
  const m = typeof s === 'string' && s.match(
    /^(\d{4})-(\d{2})-(\d{2})(?:[\s\S](\d{2})(?::(\d{2})(?::(\d{2})(?:\.(\d{3}|\d{6}))?)?)?([+-]\d{2}:\d{2})?)?$/u);
  if (!m) return null;
  const [y, mo, d, h, mi, sec] = m.slice(1, 7).map((x) => Number(x || 0));
  const ms = Date.UTC(y, mo - 1, d, h, mi, sec);
  const check = new Date(ms);
  if (y < 1 || check.getUTCMonth() !== mo - 1 || check.getUTCDate() !== d || h > 23 || mi > 59 || sec > 59) return null;
  const frac = (m[7] || '').padEnd(6, '0');
  return { ms: ms + Number(frac.slice(0, 3)), micro: Number(frac.slice(3)), tz: m[8] || '' };
}

// dt + timedelta(minutes=minutes)
function pyAddMinutes(dt, minutes) {
  const micros = dt.micro + Math.round(minutes * 60e6);
  const carry = Math.floor(micros / 1000);
  return { ms: dt.ms + carry, micro: micros - carry * 1000, tz: dt.tz };
}

// datetime.isoformat()
function pyIsoformat(dt) {
  const t = new Date(dt.ms);
  const p = (n, w = 2) => String(n).padStart(w, '0');
  let out = `${p(t.getUTCFullYear(), 4)}-${p(t.getUTCMonth() + 1)}-${p(t.getUTCDate())}`
    + `T${p(t.getUTCHours())}:${p(t.getUTCMinutes())}:${p(t.getUTCSeconds())}`;
  if (t.getUTCMilliseconds() || dt.micro) out += '.' + p(t.getUTCMilliseconds(), 3) + p(dt.micro, 3);
  return out + dt.tz;
}

// Python's random.Random(seed) for string seeds (Mersenne Twister), so the sample
// fares here are identical to the ones server.py generates for the same search.
class PyRandom {
  constructor(seed) {
    // random.seed(str): int.from_bytes(seed + sha512(seed)) split into 32-bit words, low first.
    const raw = Buffer.from(seed, 'utf8');
    let bytes = Buffer.concat([raw, crypto.createHash('sha512').update(raw).digest()]);
    let start = 0;
    while (start < bytes.length && bytes[start] === 0) start++;
    bytes = Buffer.from(bytes.subarray(start)).reverse();
    bytes = Buffer.concat([bytes, Buffer.alloc((4 - (bytes.length % 4)) % 4)]);
    const key = [];
    for (let i = 0; i < bytes.length; i += 4) key.push(bytes.readUInt32LE(i));
    if (!key.length) key.push(0);

    const mt = new Array(624);
    mt[0] = 19650218;
    for (let i = 1; i < 624; i++) mt[i] = (Math.imul(1812433253, mt[i - 1] ^ (mt[i - 1] >>> 30)) + i) >>> 0;
    let i = 1;
    let j = 0;
    for (let k = Math.max(624, key.length); k; k--) {
      mt[i] = ((mt[i] ^ Math.imul(mt[i - 1] ^ (mt[i - 1] >>> 30), 1664525)) + key[j] + j) >>> 0;
      i++;
      j++;
      if (i >= 624) { mt[0] = mt[623]; i = 1; }
      if (j >= key.length) j = 0;
    }
    for (let k = 623; k; k--) {
      mt[i] = ((mt[i] ^ Math.imul(mt[i - 1] ^ (mt[i - 1] >>> 30), 1566083941)) - i) >>> 0;
      i++;
      if (i >= 624) { mt[0] = mt[623]; i = 1; }
    }
    mt[0] = 0x80000000;
    this.mt = mt;
    this.index = 624;
  }

  next32() {
    const mt = this.mt;
    if (this.index >= 624) {
      for (let k = 0; k < 624; k++) {
        const y = (mt[k] & 0x80000000) | (mt[(k + 1) % 624] & 0x7fffffff);
        mt[k] = (mt[(k + 397) % 624] ^ (y >>> 1) ^ (y & 1 ? 0x9908b0df : 0)) >>> 0;
      }
      this.index = 0;
    }
    let y = mt[this.index++];
    y ^= y >>> 11;
    y ^= (y << 7) & 0x9d2c5680;
    y ^= (y << 15) & 0xefc60000;
    y ^= y >>> 18;
    return y >>> 0;
  }

  below(n) {
    const bits = n.toString(2).length;
    let r;
    do r = this.next32() >>> (32 - bits); while (r >= n);
    return r;
  }

  randint(a, b) { return a + this.below(b - a + 1); }

  choice(items) { return items[this.below(items.length)]; }

  sample(items, k) {
    const pool = items.slice();
    const n = pool.length;
    const result = [];
    for (let i = 0; i < k; i++) {
      const j = this.below(n - i);
      result.push(pool[j]);
      pool[j] = pool[n - i - 1];
    }
    return result;
  }
}


// --------------------------------------------------------------------------- flights
const CABINS = new Set(['economy', 'premium_economy', 'business', 'first']);

function providerName() {
  if (['duffel', 'travelpayouts', 'demo'].includes(FLIGHT_PROVIDER)) return FLIGHT_PROVIDER;
  if (DUFFEL_TOKEN) return 'duffel';
  if (TRAVELPAYOUTS_TOKEN) return 'travelpayouts';
  return 'demo';
}

function isoMinutes(duration) {
  // PT7H25M -> 445
  if (!pyTruthy(duration)) return null;
  const m = String(duration).match(/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?/);
  if (!m) return null;
  const [d, h, mi] = m.slice(1).map((x) => Number(x || 0));
  return d * 1440 + h * 60 + mi;
}

async function fetchJson(url, timeoutSec, options = {}) {
  const resp = await fetch(url, { ...options, signal: AbortSignal.timeout(timeoutSec * 1000) });
  const text = await resp.text();
  if (resp.status >= 400) throw new Error(text.slice(0, 300));
  return JSON.parse(text);
}

async function searchDuffel(q) {
  const slices = [{ origin: q.from, destination: q.to, departure_date: q.date }];
  if (q.return) slices.push({ origin: q.to, destination: q.from, departure_date: q.return });
  const body = {
    data: {
      slices,
      passengers: Array.from({ length: q.adults }, () => ({ type: 'adult' })),
      cabin_class: q.cabin,
    },
  };
  const payload = await fetchJson('https://api.duffel.com/air/offer_requests?return_offers=true&supplier_timeout=20000', 40, {
    method: 'POST',
    body: JSON.stringify(body),
    headers: {
      Authorization: `Bearer ${DUFFEL_TOKEN}`,
      'Duffel-Version': 'v2',
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  });
  const results = [];
  for (const offer of pyGet(pyGet(payload, 'data', {}), 'offers', []).slice(0, 40)) {
    const offerSlices = pyNeed(offer, 'slices');
    const outSlice = pyNeed(offerSlices, 0);
    const segs = pyNeed(outSlice, 'segments');
    const first = pyNeed(segs, 0);
    const last = pyNeed(segs, segs.length - 1);
    const carrier = pyOr(pyGet(first, 'marketing_carrier'), {});
    const owner = pyOr(pyGet(offer, 'owner'), {});
    results.push({
      id: pyNeed(offer, 'id'),
      airline: pyOr(pyGet(owner, 'name'), pyGet(carrier, 'name', '')),
      airlineCode: pyOr(pyGet(owner, 'iata_code'), pyGet(carrier, 'iata_code', '')),
      flightNumber: pyStr(pyGet(carrier, 'iata_code', '')) + pyStr(pyGet(first, 'marketing_carrier_flight_number', '')),
      from: pyNeed(pyNeed(first, 'origin'), 'iata_code'),
      to: pyNeed(pyNeed(last, 'destination'), 'iata_code'),
      departAt: pyNeed(first, 'departing_at'),
      arriveAt: pyNeed(last, 'arriving_at'),
      durationMin: isoMinutes(pyGet(outSlice, 'duration')),
      stops: segs.length - 1,
      price: pyFloat(pyNeed(offer, 'total_amount')),
      currency: pyNeed(offer, 'total_currency'),
      cabin: q.cabin,
      returnTrip: offerSlices.length > 1,
      bookingUrl: null,
    });
  }
  return results;
}

async function searchTravelpayouts(q) {
  const params = new URLSearchParams({
    origin: q.from,
    destination: q.to,
    departure_at: q.date,
    currency: q.currency.toLowerCase(),
    sorting: 'price',
    direct: 'false',
    limit: '30',
    one_way: q.return ? 'false' : 'true',
    token: TRAVELPAYOUTS_TOKEN,
  });
  if (q.return) params.set('return_at', q.return);
  const url = 'https://api.travelpayouts.com/aviasales/v3/prices_for_dates?' + params;
  const payload = await fetchJson(url, 30, { headers: { Accept: 'application/json' } });
  const results = [];
  for (const item of pyGet(payload, 'data', [])) {
    const depart = pyGet(item, 'departure_at', '');
    const dur = pyOr(pyOr(pyGet(item, 'duration_to'), pyGet(item, 'duration')), 0);
    let arrive = '';
    const dt = pyFromIsoformat(depart);
    if (dt && typeof dur === 'number') arrive = pyIsoformat(pyAddMinutes(dt, dur));
    const link = pyGet(item, 'link');
    let booking = null;
    if (pyTruthy(link)) {
      booking = 'https://www.aviasales.com' + link;
      if (TRAVELPAYOUTS_MARKER) booking += (booking.includes('?') ? '&' : '?') + 'marker=' + TRAVELPAYOUTS_MARKER;
    }
    results.push({
      id: `tp-${results.length}`,
      airline: pyGet(item, 'airline', ''),
      airlineCode: pyGet(item, 'airline', ''),
      flightNumber: pyStr(pyGet(item, 'airline', '')) + pyStr(pyGet(item, 'flight_number', '')),
      from: pyOr(pyGet(item, 'origin_airport'), pyGet(item, 'origin')),
      to: pyOr(pyGet(item, 'destination_airport'), pyGet(item, 'destination')),
      departAt: depart,
      arriveAt: arrive,
      durationMin: pyOr(dur, null),
      stops: pyGet(item, 'transfers', 0),
      price: pyFloat(pyGet(item, 'price', 0)) * q.adults,
      currency: String(pyGet(payload, 'currency', 'usd')).toUpperCase(),
      cabin: 'economy',
      returnTrip: Boolean(q.return),
      bookingUrl: booking,
    });
  }
  return results;
}

const DEMO_AIRLINES = [
  ['Emirates', 'EK'], ['Qatar Airways', 'QR'], ['Etihad Airways', 'EY'], ['Turkish Airlines', 'TK'],
  ['flydubai', 'FZ'], ['Saudia', 'SV'], ['PIA', 'PK'], ['Air Arabia', 'G9'], ['Lufthansa', 'LH'],
  ['British Airways', 'BA'], ['Singapore Airlines', 'SQ'], ['Oman Air', 'WY'],
];

function searchDemo(q) {
  // Realistic sample fares so the engine works before an API key is configured.
  const rng = new PyRandom(`${q.from}${q.to}${q.date}${q.cabin}`);
  const base = 180 + ([...(q.from + q.to)].reduce((sum, c) => sum + c.codePointAt(0), 0) % 9) * 85;
  const mult = { economy: 1, premium_economy: 1.7, business: 3.4, first: 5.5 }[q.cabin];
  const day = pyFromIsoformat(q.date);
  return rng.sample(DEMO_AIRLINES, 8).map(([name, code], i) => {
    const dep = pyAddMinutes(day, rng.randint(0, 22) * 60 + rng.choice([0, 10, 25, 40, 55]));
    const stops = rng.choice([0, 0, 1, 1, 2]);
    const dur = rng.randint(150, 420) + stops * rng.randint(70, 200);
    let price = (base + rng.randint(-60, 260) + stops * -35) * mult * q.adults;
    if (q.return) price *= 1.8;
    return {
      id: `demo-${i}`,
      airline: name,
      airlineCode: code,
      flightNumber: `${code}${rng.randint(100, 999)}`,
      from: q.from,
      to: q.to,
      departAt: pyIsoformat(dep),
      arriveAt: pyIsoformat(pyAddMinutes(dep, dur)),
      durationMin: dur,
      stops,
      price: price < 79 ? 79 : Math.round(price * 100) / 100,
      currency: 'USD',
      cabin: q.cabin,
      returnTrip: Boolean(q.return),
      bookingUrl: null,
    };
  });
}

class BadRequest extends Error {}

async function searchFlights(params) {
  const one = (key, fallback = '') => (params[key] ? params[key][0] : fallback).trim();

  const adults = one('adults', '1') || '1';
  if (!/^[+-]?\d+(?:_\d+)*$/.test(adults)) {
    throw new BadRequest('invalid literal for int() with base 10: ' + pyReprStr(adults));
  }
  const q = {
    from: pySlice(one('from').toUpperCase(), 3),
    to: pySlice(one('to').toUpperCase(), 3),
    date: one('date'),
    return: one('return'),
    adults: Math.max(1, Math.min(9, parseInt(adults.replace(/_/g, ''), 10) || 1)),
    cabin: one('cabin', 'economy').toLowerCase(),
    currency: one('currency', 'usd'),
  };
  if (!(/^[A-Z]{3}$/.test(q.from) && /^[A-Z]{3}$/.test(q.to))) {
    throw new BadRequest('Please choose valid departure and destination airports.');
  }
  if (q.from === q.to) throw new BadRequest('Departure and destination must be different.');
  if (!pyFromIsoformat(q.date) || (q.return && !pyFromIsoformat(q.return))) {
    throw new BadRequest('Please choose a valid travel date.');
  }
  if (!CABINS.has(q.cabin)) q.cabin = 'economy';

  let provider = providerName();
  const started = Date.now();
  let note = null;
  let results;
  try {
    if (provider === 'duffel') results = await searchDuffel(q);
    else if (provider === 'travelpayouts') results = await searchTravelpayouts(q);
    else results = searchDemo(q);
  } catch (exc) {
    console.error(`[flights] ${provider} failed: ${exc.message}`);
    note = `Live fares from ${provider} are unavailable right now; showing sample fares.`;
    provider = 'demo';
    results = searchDemo(q);
  }

  results.sort((a, b) => a.price - b.price);
  return {
    provider,
    live: provider !== 'demo',
    note,
    query: q,
    tookMs: Date.now() - started,
    results,
  };
}


// --------------------------------------------------------------------------- HTTP
// urllib.parse.parse_qs: blank values are dropped, every value is kept in order.
function parseQs(query) {
  const params = {};
  for (const pair of query.split('&')) {
    const at = pair.indexOf('=');
    if (at < 0 || at === pair.length - 1) continue;
    const decode = (s) => {
      s = s.replace(/\+/g, ' ');
      try { return decodeURIComponent(s); } catch (e) { return unescape(s); }
    };
    (params[decode(pair.slice(0, at))] ||= []).push(decode(pair.slice(at + 1)));
  }
  return params;
}

const MIME = {
  '.html': 'text/html', '.htm': 'text/html', '.css': 'text/css', '.js': 'application/javascript',
  '.json': 'application/json', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg', '.gif': 'image/gif', '.webp': 'image/webp', '.ico': 'image/vnd.microsoft.icon',
  '.woff': 'font/woff', '.woff2': 'font/woff2', '.txt': 'text/plain', '.xml': 'text/xml', '.pdf': 'application/pdf',
};

function sendJson(res, payload, status = 200) {
  const body = Buffer.from(JSON.stringify(payload), 'utf8');
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': body.length,
    'Cache-Control': 'no-store',
  });
  res.end(body);
}

// The same error page Python's http.server sends.
const ERRORS = {
  404: ['NOT_FOUND', 'Nothing matches the given URI'],
  500: ['INTERNAL_SERVER_ERROR', 'Server got itself in trouble'],
  501: ['NOT_IMPLEMENTED', 'Server does not support this operation'],
};

function sendError(res, status, message) {
  const [name, explain] = ERRORS[status];
  const html = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  const body = Buffer.from(`<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title>Error response</title>
    </head>
    <body>
        <h1>Error response</h1>
        <p>Error code: ${status}</p>
        <p>Message: ${html(message)}.</p>
        <p>Error code explanation: HTTPStatus.${name} - ${explain}.</p>
    </body>
</html>
`);
  res.writeHead(status, { 'Content-Type': 'text/html;charset=utf-8', 'Content-Length': body.length, Connection: 'close' });
  res.end(body);
}

// SimpleHTTPRequestHandler: files from public/, index.html for folders, If-Modified-Since.
function serveStatic(req, res, urlPath) {
  let decoded;
  try { decoded = decodeURIComponent(urlPath); } catch (e) { decoded = urlPath; }
  const parts = decoded.split('/').filter((p) => p && p !== '.' && p !== '..' && !p.includes('\\'));
  let file = path.join(PUBLIC, ...parts);
  let stat = fs.existsSync(file) ? fs.statSync(file) : null;
  if (stat && stat.isDirectory()) {
    if (!urlPath.endsWith('/')) {
      res.writeHead(301, { Location: urlPath + '/', 'Content-Length': 0 });
      return res.end();
    }
    file = path.join(file, 'index.html');
    stat = fs.existsSync(file) ? fs.statSync(file) : null;
  }
  if (!stat || !stat.isFile()) return sendError(res, 404, 'File not found');

  const modified = new Date(Math.floor(stat.mtimeMs / 1000) * 1000);
  const since = Date.parse(req.headers['if-modified-since'] || '');
  if (!req.headers['if-none-match'] && !Number.isNaN(since) && modified.getTime() <= since) {
    res.writeHead(304);
    return res.end();
  }
  res.writeHead(200, {
    'Content-Type': MIME[path.extname(file).toLowerCase()] || 'application/octet-stream',
    'Content-Length': stat.size,
    'Last-Modified': modified.toUTCString(),
  });
  if (req.method === 'HEAD') return res.end();
  fs.createReadStream(file).pipe(res);
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    if (Number(req.headers['content-length'] || 0) > 2000000) {
      req.resume();
      return reject(new BadRequest('Request too large'));
    }
    const chunks = [];
    let size = 0;
    req.on('data', (chunk) => {
      size += chunk.length;
      if (size <= 2000000) chunks.push(chunk);
    });
    req.on('end', () => {
      if (size > 2000000) return reject(new BadRequest('Request too large'));
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}'));
      } catch (e) {
        reject(new BadRequest('Invalid JSON'));
      }
    });
    req.on('error', reject);
  });
}

const isObject = (v) => v !== null && typeof v === 'object' && !Array.isArray(v);
const hasId = (item, id) => isObject(item) && item.id === id;
const matchId = (urlPath, prefix) => {
  const m = urlPath.match(new RegExp(`^/api/${prefix}/([\\w-]+)$`));
  return m ? m[1] : null;
};

async function handle(req, res) {
  const url = req.url || '/';
  const q = url.indexOf('?');
  const urlPath = (q < 0 ? url : url.slice(0, q)).split('#')[0];
  const query = q < 0 ? '' : url.slice(q + 1).split('#')[0];
  const isAdmin = () => req.headers['x-admin-key'] === ADMIN_PASSWORD;
  const requireAdmin = () => {
    if (isAdmin()) return true;
    sendJson(res, { error: 'Unauthorized' }, 401);
    return false;
  };
  const body = async () => {
    try {
      return { ok: true, data: await readBody(req) };
    } catch (exc) {
      if (!(exc instanceof BadRequest)) throw exc;
      sendJson(res, { error: 'Invalid JSON' }, 400);
      return { ok: false };
    }
  };

  if (req.method === 'GET') {
    if (urlPath === '/api/packages') return sendJson(res, readJson('packages.json', []));
    if (urlPath === '/api/settings') return sendJson(res, readJson('settings.json', {}));
    if (urlPath === '/api/airports') {
      const q = ((parseQs(query).q || [''])[0]).trim().toLowerCase();
      let airports = readJson('airports.json', []);
      if (q) {
        airports = airports.filter((a) => [a.code, a.city, a.name, a.country].some((v) => v.toLowerCase().includes(q)));
        const key = (a) => [a.code.toLowerCase() !== q, !a.city.toLowerCase().startsWith(q)];
        airports.sort((a, b) => {
          const [ka, kb] = [key(a), key(b)];
          return (ka[0] - kb[0]) || (ka[1] - kb[1]);
        });
      }
      return sendJson(res, airports.slice(0, 12));
    }
    if (urlPath === '/api/flights') {
      try {
        return sendJson(res, await searchFlights(parseQs(query)));
      } catch (exc) {
        if (exc instanceof BadRequest) return sendJson(res, { error: exc.message }, 400);
        throw exc;
      }
    }
    if (urlPath === '/api/enquiries') {
      if (!requireAdmin()) return;
      return sendJson(res, readJson('enquiries.json', []));
    }
    if (urlPath === '/api/status') return sendJson(res, { flightProvider: providerName() });
    return serveStatic(req, res, urlPath === '/admin' ? '/admin.html' : urlPath);
  }

  if (req.method === 'HEAD') return serveStatic(req, res, urlPath);

  if (req.method === 'POST') {
    const parsed = await body();
    if (!parsed.ok) return;
    const data = parsed.data;
    if (!isObject(data)) return sendJson(res, { error: 'Invalid JSON' }, 400);

    if (urlPath === '/api/admin/login') {
      if (data.password === ADMIN_PASSWORD) return sendJson(res, { ok: true });
      return sendJson(res, { error: 'Wrong password' }, 401);
    }

    if (urlPath === '/api/enquiries') {
      // Every enquiry is logged before the visitor is sent to WhatsApp.
      const enquiries = readJson('enquiries.json', []);
      data.id = pyHex(10);
      data.createdAt = localIsoSeconds();
      const entry = {};
      for (const [k, v] of Object.entries(data)) entry[k] = pySlice(pyStr(v), 1000);
      enquiries.unshift(entry);
      writeJson('enquiries.json', enquiries.slice(0, 2000));
      return sendJson(res, { ok: true });
    }

    if (urlPath === '/api/packages') {
      if (!requireAdmin()) return;
      const packages = readJson('packages.json', []);
      data.id = pyOr(data.id, pyHex(8));
      packages.push(data);
      writeJson('packages.json', packages);
      return sendJson(res, data, 201);
    }

    return sendJson(res, { error: 'Not found' }, 404);
  }

  if (req.method === 'PUT') {
    if (!requireAdmin()) return;
    const parsed = await body();
    if (!parsed.ok) return;
    const data = parsed.data;

    if (urlPath === '/api/settings') {
      writeJson('settings.json', data);
      return sendJson(res, data);
    }

    if (urlPath === '/api/packages') {  // bulk replace (reorder / import)
      if (!Array.isArray(data)) return sendJson(res, { error: 'Expected a list' }, 400);
      writeJson('packages.json', data);
      return sendJson(res, data);
    }

    const id = matchId(urlPath, 'packages');
    if (id !== null) {
      if (!isObject(data)) return sendJson(res, { error: 'Invalid JSON' }, 400);
      const packages = readJson('packages.json', []);
      const i = packages.findIndex((p) => hasId(p, id));
      if (i < 0) return sendJson(res, { error: 'Package not found' }, 404);
      data.id = packages[i].id;
      packages[i] = data;
      writeJson('packages.json', packages);
      return sendJson(res, data);
    }

    return sendJson(res, { error: 'Not found' }, 404);
  }

  if (req.method === 'DELETE') {
    if (!requireAdmin()) return;
    let id = matchId(urlPath, 'packages');
    if (id !== null) {
      const packages = readJson('packages.json', []);
      const remaining = packages.filter((p) => !hasId(p, id));
      writeJson('packages.json', remaining);
      return sendJson(res, { ok: true, deleted: packages.length - remaining.length });
    }
    id = matchId(urlPath, 'enquiries');
    if (id !== null) {
      writeJson('enquiries.json', readJson('enquiries.json', []).filter((e) => !hasId(e, id)));
      return sendJson(res, { ok: true });
    }
    return sendJson(res, { error: 'Not found' }, 404);
  }

  return sendError(res, 501, `Unsupported method ('${req.method}')`);
}

// datetime.now().isoformat(timespec="seconds"), in the server's local time.
function localIsoSeconds() {
  const t = new Date();
  const p = (n) => String(n).padStart(2, '0');
  return `${t.getFullYear()}-${p(t.getMonth() + 1)}-${p(t.getDate())}T${p(t.getHours())}:${p(t.getMinutes())}:${p(t.getSeconds())}`;
}

function main() {
  const rawPort = process.argv[2] || env('PORT', '8000');
  const port = /^\d+$/.test(String(rawPort)) ? Number(rawPort) : rawPort;  // a hosting panel may hand over a socket path
  // A PORT set by the hosting panel (not .env) means a proxy sits in front: listen on every interface.
  const host = REAL_ENV.HOST || (REAL_ENV.PORT && !process.argv[2] ? undefined : env('HOST', '127.0.0.1'));

  const server = http.createServer((req, res) => {
    res.on('finish', () => console.error(`${new Date().toUTCString()} - "${req.method} ${req.url} HTTP/${req.httpVersion}" ${res.statusCode} -`));
    res.setHeader('Server', 'OKTravel/1.0');
    handle(req, res).catch((exc) => {
      console.error(exc);
      if (!res.headersSent) sendError(res, 500, 'Internal Server Error');
      else res.end();
    });
  });
  server.listen(port, host, () => {
    console.log(`OK FOR TRAVEL AND TOURISM CO running on http://localhost:${port}`);
    console.log(`Admin panel: http://localhost:${port}/admin   |   flight provider: ${providerName()}`);
    if (ADMIN_PASSWORD === 'admin123') console.log('WARNING: default admin password in use - set ADMIN_PASSWORD in .env');
  });
}

main();
