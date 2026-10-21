"""
OK FOR TRAVEL AND TOURISM CO - website + travel engine server.

Zero dependencies: runs on the Python 3.8+ standard library.

    python server.py            -> http://localhost:8000
    python server.py 9000       -> custom port

Configuration lives in a `.env` file next to this script (see .env.example).
"""

import json
import os
import random
import re
import sys
import threading
import time
import uuid
from datetime import datetime, timedelta
from http import HTTPStatus
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import parse_qs, urlencode, urlparse
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parent
PUBLIC = ROOT / "public"
DATA = ROOT / "data"
LOCK = threading.Lock()


# --------------------------------------------------------------------------- config
def load_env():
    env_file = ROOT / ".env"
    if env_file.exists():
        for line in env_file.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, value = line.split("=", 1)
            os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))


load_env()
ADMIN_PASSWORD = os.environ.get("ADMIN_PASSWORD", "admin123")
FLIGHT_PROVIDER = os.environ.get("FLIGHT_PROVIDER", "auto").lower()
DUFFEL_TOKEN = os.environ.get("DUFFEL_ACCESS_TOKEN", "")
TRAVELPAYOUTS_TOKEN = os.environ.get("TRAVELPAYOUTS_TOKEN", "")
TRAVELPAYOUTS_MARKER = os.environ.get("TRAVELPAYOUTS_MARKER", "")


# --------------------------------------------------------------------------- storage
def read_json(name, default):
    path = DATA / name
    if not path.exists():
        return default
    with LOCK:
        return json.loads(path.read_text(encoding="utf-8"))


def write_json(name, payload):
    DATA.mkdir(exist_ok=True)
    tmp = DATA / (name + ".tmp")
    with LOCK:
        tmp.write_text(json.dumps(payload, indent=2, ensure_ascii=False), encoding="utf-8")
        tmp.replace(DATA / name)


# --------------------------------------------------------------------------- flights
CABINS = {"economy", "premium_economy", "business", "first"}


def provider_name():
    if FLIGHT_PROVIDER in ("duffel", "travelpayouts", "demo"):
        return FLIGHT_PROVIDER
    if DUFFEL_TOKEN:
        return "duffel"
    if TRAVELPAYOUTS_TOKEN:
        return "travelpayouts"
    return "demo"


def iso_minutes(duration):
    """PT7H25M -> 445"""
    if not duration:
        return None
    m = re.match(r"P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?", duration)
    if not m:
        return None
    d, h, mi = (int(x or 0) for x in m.groups())
    return d * 1440 + h * 60 + mi


def search_duffel(q):
    slices = [{"origin": q["from"], "destination": q["to"], "departure_date": q["date"]}]
    if q.get("return"):
        slices.append({"origin": q["to"], "destination": q["from"], "departure_date": q["return"]})
    body = {
        "data": {
            "slices": slices,
            "passengers": [{"type": "adult"} for _ in range(q["adults"])],
            "cabin_class": q["cabin"],
        }
    }
    req = Request(
        "https://api.duffel.com/air/offer_requests?return_offers=true&supplier_timeout=20000",
        data=json.dumps(body).encode(),
        method="POST",
        headers={
            "Authorization": f"Bearer {DUFFEL_TOKEN}",
            "Duffel-Version": "v2",
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
    )
    with urlopen(req, timeout=40) as resp:
        payload = json.loads(resp.read().decode())
    results = []
    for offer in payload.get("data", {}).get("offers", [])[:40]:
        out_slice = offer["slices"][0]
        segs = out_slice["segments"]
        carrier = segs[0].get("marketing_carrier") or {}
        results.append({
            "id": offer["id"],
            "airline": (offer.get("owner") or {}).get("name") or carrier.get("name", ""),
            "airlineCode": (offer.get("owner") or {}).get("iata_code") or carrier.get("iata_code", ""),
            "flightNumber": f"{carrier.get('iata_code', '')}{segs[0].get('marketing_carrier_flight_number', '')}",
            "from": segs[0]["origin"]["iata_code"],
            "to": segs[-1]["destination"]["iata_code"],
            "departAt": segs[0]["departing_at"],
            "arriveAt": segs[-1]["arriving_at"],
            "durationMin": iso_minutes(out_slice.get("duration")),
            "stops": len(segs) - 1,
            "price": float(offer["total_amount"]),
            "currency": offer["total_currency"],
            "cabin": q["cabin"],
            "returnTrip": len(offer["slices"]) > 1,
            "bookingUrl": None,
        })
    return results


def search_travelpayouts(q):
    params = {
        "origin": q["from"],
        "destination": q["to"],
        "departure_at": q["date"],
        "currency": q.get("currency", "usd").lower(),
        "sorting": "price",
        "direct": "false",
        "limit": 30,
        "one_way": "false" if q.get("return") else "true",
        "token": TRAVELPAYOUTS_TOKEN,
    }
    if q.get("return"):
        params["return_at"] = q["return"]
    url = "https://api.travelpayouts.com/aviasales/v3/prices_for_dates?" + urlencode(params)
    with urlopen(Request(url, headers={"Accept": "application/json"}), timeout=30) as resp:
        payload = json.loads(resp.read().decode())
    results = []
    for item in payload.get("data", []):
        depart = item.get("departure_at", "")
        dur = item.get("duration_to") or item.get("duration") or 0
        arrive = ""
        try:
            arrive = (datetime.fromisoformat(depart) + timedelta(minutes=dur)).isoformat()
        except ValueError:
            pass
        link = item.get("link")
        booking = None
        if link:
            booking = "https://www.aviasales.com" + link
            if TRAVELPAYOUTS_MARKER:
                booking += ("&" if "?" in booking else "?") + "marker=" + TRAVELPAYOUTS_MARKER
        results.append({
            "id": f"tp-{len(results)}",
            "airline": item.get("airline", ""),
            "airlineCode": item.get("airline", ""),
            "flightNumber": f"{item.get('airline', '')}{item.get('flight_number', '')}",
            "from": item.get("origin_airport") or item.get("origin"),
            "to": item.get("destination_airport") or item.get("destination"),
            "departAt": depart,
            "arriveAt": arrive,
            "durationMin": dur or None,
            "stops": item.get("transfers", 0),
            "price": float(item.get("price", 0)) * q["adults"],
            "currency": payload.get("currency", "usd").upper(),
            "cabin": "economy",
            "returnTrip": bool(q.get("return")),
            "bookingUrl": booking,
        })
    return results


DEMO_AIRLINES = [
    ("Emirates", "EK"), ("Qatar Airways", "QR"), ("Etihad Airways", "EY"), ("Turkish Airlines", "TK"),
    ("flydubai", "FZ"), ("Saudia", "SV"), ("PIA", "PK"), ("Air Arabia", "G9"), ("Lufthansa", "LH"),
    ("British Airways", "BA"), ("Singapore Airlines", "SQ"), ("Oman Air", "WY"),
]


def search_demo(q):
    """Realistic sample fares so the engine works before an API key is configured."""
    seed = f"{q['from']}{q['to']}{q['date']}{q['cabin']}"
    rng = random.Random(seed)
    base = 180 + (sum(map(ord, q["from"] + q["to"])) % 9) * 85
    mult = {"economy": 1, "premium_economy": 1.7, "business": 3.4, "first": 5.5}[q["cabin"]]
    day = datetime.fromisoformat(q["date"])
    results = []
    for i, (name, code) in enumerate(rng.sample(DEMO_AIRLINES, 8)):
        dep = day + timedelta(hours=rng.randint(0, 22), minutes=rng.choice([0, 10, 25, 40, 55]))
        stops = rng.choice([0, 0, 1, 1, 2])
        dur = rng.randint(150, 420) + stops * rng.randint(70, 200)
        price = (base + rng.randint(-60, 260) + stops * -35) * mult * q["adults"]
        if q.get("return"):
            price *= 1.8
        results.append({
            "id": f"demo-{i}",
            "airline": name,
            "airlineCode": code,
            "flightNumber": f"{code}{rng.randint(100, 999)}",
            "from": q["from"],
            "to": q["to"],
            "departAt": dep.isoformat(),
            "arriveAt": (dep + timedelta(minutes=dur)).isoformat(),
            "durationMin": dur,
            "stops": stops,
            "price": round(max(price, 79), 2),
            "currency": "USD",
            "cabin": q["cabin"],
            "returnTrip": bool(q.get("return")),
            "bookingUrl": None,
        })
    return results


def search_flights(params):
    def one(key, default=""):
        return (params.get(key) or [default])[0].strip()

    q = {
        "from": one("from").upper()[:3],
        "to": one("to").upper()[:3],
        "date": one("date"),
        "return": one("return"),
        "adults": max(1, min(9, int(one("adults", "1") or 1))),
        "cabin": one("cabin", "economy").lower(),
        "currency": one("currency", "usd"),
    }
    if not (re.fullmatch(r"[A-Z]{3}", q["from"]) and re.fullmatch(r"[A-Z]{3}", q["to"])):
        raise ValueError("Please choose valid departure and destination airports.")
    if q["from"] == q["to"]:
        raise ValueError("Departure and destination must be different.")
    try:
        datetime.fromisoformat(q["date"])
        if q["return"]:
            datetime.fromisoformat(q["return"])
    except ValueError:
        raise ValueError("Please choose a valid travel date.")
    if q["cabin"] not in CABINS:
        q["cabin"] = "economy"

    provider = provider_name()
    started = time.time()
    note = None
    try:
        if provider == "duffel":
            results = search_duffel(q)
        elif provider == "travelpayouts":
            results = search_travelpayouts(q)
        else:
            results = search_demo(q)
    except (HTTPError, URLError, TimeoutError, KeyError, ValueError) as exc:
        detail = exc.read().decode(errors="ignore")[:300] if isinstance(exc, HTTPError) else str(exc)
        print(f"[flights] {provider} failed: {detail}", file=sys.stderr)
        note = f"Live fares from {provider} are unavailable right now; showing sample fares."
        provider, results = "demo", search_demo(q)

    results.sort(key=lambda r: r["price"])
    return {
        "provider": provider,
        "live": provider != "demo",
        "note": note,
        "query": q,
        "tookMs": int((time.time() - started) * 1000),
        "results": results,
    }


# --------------------------------------------------------------------------- HTTP
class Handler(SimpleHTTPRequestHandler):
    server_version = "OKTravel/1.0"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=str(PUBLIC), **kwargs)

    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.log_date_time_string(), fmt % args))

    # helpers -----------------------------------------------------------------
    def send_json(self, payload, status=HTTPStatus.OK):
        body = json.dumps(payload, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "no-store")
        self.end_headers()
        self.wfile.write(body)

    def body_json(self):
        length = int(self.headers.get("Content-Length") or 0)
        if length > 2_000_000:
            raise ValueError("Request too large")
        raw = self.rfile.read(length) if length else b"{}"
        return json.loads(raw.decode() or "{}")

    def is_admin(self):
        return self.headers.get("X-Admin-Key", "") == ADMIN_PASSWORD

    def require_admin(self):
        if not self.is_admin():
            self.send_json({"error": "Unauthorized"}, HTTPStatus.UNAUTHORIZED)
            return False
        return True

    # routing -----------------------------------------------------------------
    def do_GET(self):
        url = urlparse(self.path)
        path = url.path
        if path == "/api/packages":
            return self.send_json(read_json("packages.json", []))
        if path == "/api/settings":
            return self.send_json(read_json("settings.json", {}))
        if path == "/api/airports":
            q = (parse_qs(url.query).get("q") or [""])[0].strip().lower()
            airports = read_json("airports.json", [])
            if q:
                airports = [a for a in airports if q in a["code"].lower() or q in a["city"].lower()
                            or q in a["name"].lower() or q in a["country"].lower()]
                airports.sort(key=lambda a: (a["code"].lower() != q, not a["city"].lower().startswith(q)))
            return self.send_json(airports[:12])
        if path == "/api/flights":
            try:
                return self.send_json(search_flights(parse_qs(url.query)))
            except ValueError as exc:
                return self.send_json({"error": str(exc)}, HTTPStatus.BAD_REQUEST)
        if path == "/api/enquiries":
            if not self.require_admin():
                return
            return self.send_json(read_json("enquiries.json", []))
        if path == "/api/status":
            return self.send_json({"flightProvider": provider_name()})
        if path == "/admin":
            self.path = "/admin.html"
        return super().do_GET()

    def do_POST(self):
        path = urlparse(self.path).path
        try:
            data = self.body_json()
        except (ValueError, json.JSONDecodeError):
            return self.send_json({"error": "Invalid JSON"}, HTTPStatus.BAD_REQUEST)

        if path == "/api/admin/login":
            if data.get("password") == ADMIN_PASSWORD:
                return self.send_json({"ok": True})
            return self.send_json({"error": "Wrong password"}, HTTPStatus.UNAUTHORIZED)

        if path == "/api/enquiries":
            # Every enquiry is logged before the visitor is sent to WhatsApp.
            enquiries = read_json("enquiries.json", [])
            data["id"] = uuid.uuid4().hex[:10]
            data["createdAt"] = datetime.now().isoformat(timespec="seconds")
            enquiries.insert(0, {k: str(v)[:1000] for k, v in data.items()})
            write_json("enquiries.json", enquiries[:2000])
            return self.send_json({"ok": True})

        if path == "/api/packages":
            if not self.require_admin():
                return
            packages = read_json("packages.json", [])
            data["id"] = data.get("id") or uuid.uuid4().hex[:8]
            packages.append(data)
            write_json("packages.json", packages)
            return self.send_json(data, HTTPStatus.CREATED)

        self.send_json({"error": "Not found"}, HTTPStatus.NOT_FOUND)

    def do_PUT(self):
        path = urlparse(self.path).path
        if not self.require_admin():
            return
        try:
            data = self.body_json()
        except (ValueError, json.JSONDecodeError):
            return self.send_json({"error": "Invalid JSON"}, HTTPStatus.BAD_REQUEST)

        if path == "/api/settings":
            write_json("settings.json", data)
            return self.send_json(data)

        if path == "/api/packages":  # bulk replace (reorder / import)
            if not isinstance(data, list):
                return self.send_json({"error": "Expected a list"}, HTTPStatus.BAD_REQUEST)
            write_json("packages.json", data)
            return self.send_json(data)

        m = re.fullmatch(r"/api/packages/([\w-]+)", path)
        if m:
            packages = read_json("packages.json", [])
            for i, p in enumerate(packages):
                if p.get("id") == m.group(1):
                    data["id"] = p["id"]
                    packages[i] = data
                    write_json("packages.json", packages)
                    return self.send_json(data)
            return self.send_json({"error": "Package not found"}, HTTPStatus.NOT_FOUND)

        self.send_json({"error": "Not found"}, HTTPStatus.NOT_FOUND)

    def do_DELETE(self):
        path = urlparse(self.path).path
        if not self.require_admin():
            return
        m = re.fullmatch(r"/api/packages/([\w-]+)", path)
        if m:
            packages = read_json("packages.json", [])
            remaining = [p for p in packages if p.get("id") != m.group(1)]
            write_json("packages.json", remaining)
            return self.send_json({"ok": True, "deleted": len(packages) - len(remaining)})
        m = re.fullmatch(r"/api/enquiries/([\w-]+)", path)
        if m:
            enquiries = [e for e in read_json("enquiries.json", []) if e.get("id") != m.group(1)]
            write_json("enquiries.json", enquiries)
            return self.send_json({"ok": True})
        self.send_json({"error": "Not found"}, HTTPStatus.NOT_FOUND)


def main():
    port = int(sys.argv[1]) if len(sys.argv) > 1 else int(os.environ.get("PORT", 8000))
    host = os.environ.get("HOST", "127.0.0.1")
    httpd = ThreadingHTTPServer((host, port), Handler)
    print(f"OK FOR TRAVEL AND TOURISM CO running on http://localhost:{port}")
    print(f"Admin panel: http://localhost:{port}/admin   |   flight provider: {provider_name()}")
    if ADMIN_PASSWORD == "admin123":
        print("WARNING: default admin password in use - set ADMIN_PASSWORD in .env")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()
