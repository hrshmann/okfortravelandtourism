/* OK Travel & Tourism — website behaviour. Progressive enhancement: every page works without this file. */
(function () {
  'use strict';

  var OK = window.OK || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var url = function (p) { return (OK.base || '') + p; };
  var debounce = function (fn, ms) { var t; return function () { var a = arguments, self = this; clearTimeout(t); t = setTimeout(function () { fn.apply(self, a); }, ms); }; };
  var ICON = {
    check: '<svg class="i" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>',
    info: '<svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    wa: '<svg class="i i-fill" viewBox="0 0 24 24"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.43 9.43 0 1 1 7.99 4.42zm8.02-17.45A11.3 11.3 0 0 0 12.05.72C5.8.72.72 5.8.72 12.04c0 2 .52 3.95 1.52 5.66L.62 23.6l6.03-1.58a11.3 11.3 0 0 0 5.4 1.38h.01c6.24 0 11.32-5.08 11.33-11.32 0-3.03-1.18-5.87-3.32-8.02z"/></svg>',
    plane: '<svg class="i" viewBox="0 0 24 24"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>',
    ext: '<svg class="i" viewBox="0 0 24 24"><path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>'
  };

  function toast(msg, type) {
    var wrap = $('#toasts');
    if (!wrap) return;
    var el = document.createElement('div');
    el.className = 'toast' + (type ? ' ' + type : '');
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = (type === 'error' ? ICON.info : ICON.check) + '<span>' + esc(msg) + '</span>';
    wrap.appendChild(el);
    setTimeout(function () { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; }, 4200);
    setTimeout(function () { el.remove(); }, 4600);
  }

  /* Prices: everything on screen is in the visitor's currency (FX.currency), formatted like the server does. */
  var FX = OK.fx || { currency: 'USD', symbol: '$', rates: { USD: 1 }, margin: 0 };
  function neat(v) {
    if (v < 100) return Math.ceil(v);
    var step = Math.pow(10, Math.floor(Math.log10(v)) - 2);
    return Math.ceil(v / step) * step;
  }
  /** Convert an amount from any supported currency into the visitor's currency. */
  function toVisitor(amount, from) {
    from = (from || FX.currency).toUpperCase();
    if (from === FX.currency || !FX.rates[from] || !FX.rates[FX.currency]) return amount;
    return neat(amount / FX.rates[from] * FX.rates[FX.currency] * (1 + (FX.margin || 0) / 100));
  }
  function money(n) {
    var sym = FX.symbol || FX.currency;
    var num = Math.round(n).toLocaleString(/^(INR|PKR|BDT)$/.test(FX.currency) ? 'en-IN' : 'en-US');
    return /^[A-Za-z.]+$/.test(sym) ? sym + ' ' + num : sym + num;
  }

  /* ------------------------------------------------------------- header */
  function initHeader() {
    var header = $('#siteHeader');
    if (header) {
      var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 24); };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }
    // Currency dropdown closes on outside click or Escape.
    $$('details[data-cur]').forEach(function (d) {
      document.addEventListener('click', function (e) { if (d.open && !d.contains(e.target)) d.open = false; });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && d.open) { d.open = false; $('summary', d).focus(); } });
    });

    var menu = $('#mobileMenu'), btn = $('#menuBtn');
    if (!menu || !btn) return;
    var set = function (open) {
      menu.classList.toggle('open', open);
      menu.setAttribute('aria-hidden', String(!open));
      btn.setAttribute('aria-expanded', String(open));
      document.body.classList.toggle('menu-open', open);
      if (open) setTimeout(function () { var f = $('a', menu); if (f) f.focus(); }, 200); else btn.focus();
    };
    btn.addEventListener('click', function () { set(true); });
    $('#menuClose').addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && menu.classList.contains('open')) set(false); });
  }

  /* --------------------------------------------- hero: flight path + reveal */
  function initHero() {
    var hero = $('#hero');
    if (!hero) return;
    requestAnimationFrame(function () { hero.classList.add('ready'); });

    var a = $('#trailA'), b = $('#trailB'), plane = $('#plane');
    if (!a || !plane || !a.getTotalLength) return;
    var svg = a.ownerSVGElement, box = plane.parentNode;
    var lenA = a.getTotalLength(), lenB = b.getTotalLength();
    var current = 0;
    // The SVG stretches to the hero, so convert path points to screen space before placing the plane.
    var toBox = function (pt) {
      var m = svg.getScreenCTM(), r = box.getBoundingClientRect();
      var p = svg.createSVGPoint(); p.x = pt.x; p.y = pt.y;
      p = p.matrixTransform(m);
      return { x: p.x - r.left, y: p.y - r.top };
    };
    var place = function (t) {
      current = t;
      var d = lenA * t;
      var p = toBox(a.getPointAtLength(Math.max(0, d - 0.5))), q = toBox(a.getPointAtLength(Math.min(lenA, d + 0.5)));
      var angle = Math.atan2(q.y - p.y, q.x - p.x) * 180 / Math.PI;
      plane.style.transform = 'translate(' + q.x + 'px,' + q.y + 'px) rotate(' + angle + 'deg)';
      plane.style.opacity = t > 0.02 ? '1' : '0';
      a.style.strokeDasharray = lenA; a.style.strokeDashoffset = lenA - d;
      b.style.strokeDasharray = lenB; b.style.strokeDashoffset = lenB - lenB * t;
    };
    window.addEventListener('resize', debounce(function () { place(current); }, 100));
    if (reduceMotion) { place(1); return; }
    place(0);
    var start = null, dur = 2600;
    var ease = function (t) { return 1 - Math.pow(1 - t, 3); };
    var step = function (now) {
      if (!start) start = now;
      var t = Math.min(1, (now - start) / dur);
      place(ease(t));
      if (t < 1) requestAnimationFrame(step);
    };
    setTimeout(function () { requestAnimationFrame(step); }, 250);
  }

  /* ------------------------------------------------------ departures board */
  var FLAP_CHARS = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  function initBoard() {
    var board = $('#board');
    if (!board) return;
    var pool = [];
    try { pool = JSON.parse($('#boardPool').textContent || '[]'); } catch (e) { pool = []; }
    var rows = $$('tr.row', board);

    rows.forEach(function (tr) {
      tr.addEventListener('click', function (e) { if (!e.target.closest('a')) location.href = tr.dataset.href; });
    });

    // Live clock in the agency's time zone.
    var clock = $('#boardClock');
    var tz = board.dataset.tz;
    var tick = function () {
      try {
        clock.textContent = new Intl.DateTimeFormat('en-GB', { timeZone: tz, weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date()).replace(',', ' ·');
      } catch (e) { /* unsupported time zone: keep server time */ }
    };
    tick();
    setInterval(tick, 20000);

    // Build split-flap tiles sized to the board: measure one real tile, then fill the width.
    var layout = function () {
      var table = $('table', board);
      var probe = document.createElement('span');
      probe.className = 'flaps';
      probe.style.cssText = 'position:absolute;visibility:hidden';
      probe.innerHTML = '<span class="flap">W</span><span class="flap">W</span>';
      $('.c-dest .flap-text', board).appendChild(probe);
      var tile = probe.offsetWidth / 2 + 1; // tile + half of the 2px gap
      probe.remove();
      var shown = function (col) { return getComputedStyle($('th.c-' + col, board)).display !== 'none'; };
      var showDays = shown('days'), showPrice = shown('price');
      // Rupee prices need more tiles than dollar prices.
      var priceLen = Math.max(4, Math.min(7, Math.max.apply(null, pool.map(function (p) { return String(p.price || '').length; }).concat([4]))));
      var fixed = 3 + (showDays ? 3 : 0) + (showPrice ? priceLen : 0) + 8;
      var cols = 3 + (showDays ? 1 : 0) + (showPrice ? 1 : 0);
      var avail = table.parentNode.clientWidth - cols * 8 - 4; // cell padding + rounding headroom
      var dest = Math.floor(avail / tile) - fixed;
      return { code: 3, dest: Math.max(5, Math.min(14, dest)), days: showDays ? 3 : 0, price: showPrice ? priceLen : 0, status: 8 };
    };
    var cols = ['code', 'dest', 'days', 'price', 'status'];
    var pad = function (s, n, right) {
      s = String(s || '').toUpperCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^A-Z0-9 $&]/g, ' ');
      if (s.length > n) s = s.slice(0, n);
      return right ? (' '.repeat(n) + s).slice(-n) : (s + ' '.repeat(n)).slice(0, n);
    };
    var spec = layout();
    var build = function (cell, col, text) {
      var n = spec[col];
      var target = pad(text, n, col === 'price');
      var wrap = document.createElement('span');
      wrap.className = 'flaps';
      wrap.setAttribute('aria-hidden', 'true');
      for (var i = 0; i < n; i++) {
        var f = document.createElement('span');
        f.className = 'flap';
        f.textContent = ' ';
        f.dataset.target = target[i];
        wrap.appendChild(f);
      }
      cell.appendChild(wrap);
      return wrap;
    };
    var spin = function (flaps, delayBase) {
      flaps.forEach(function (f, i) {
        var target = f.dataset.target;
        if (reduceMotion) { f.textContent = target; return; }
        var turns = 4 + Math.floor(Math.random() * 6) + (target === ' ' ? 0 : 3);
        var k = 0;
        setTimeout(function run() {
          if (k >= turns) { f.textContent = target; return; }
          f.textContent = k === turns - 1 ? target : FLAP_CHARS[Math.floor(Math.random() * FLAP_CHARS.length)];
          f.classList.remove('turn'); void f.offsetWidth; f.classList.add('turn');
          k++;
          setTimeout(run, 70);
        }, delayBase + i * 28);
      });
    };
    var render = function (tr, data, delay) {
      cols.forEach(function (col, ci) {
        var td = $('.c-' + col, tr);
        if (!td) return;
        var holder = $('.flap-text', td);
        var old = $('.flaps', holder);
        if (old) old.remove();
        if (!spec[col]) return;
        var flaps = build(holder, col, data[col]);
        spin($$('.flap', flaps), delay + ci * 120);
      });
      tr.dataset.href = data.url;
      var a = $('.c-dest a', tr);
      if (a) { a.href = data.url; a.setAttribute('aria-label', data.title); }
    };

    var start = function () {
      // Keep the readable text for screen readers; draw tiles on top.
      rows.forEach(function (tr) {
        $$('.flap-text', tr).forEach(function (el) {
          if (!el.querySelector('.sr-only')) { var t = el.textContent; el.innerHTML = '<span class="sr-only">' + esc(t) + '</span>'; }
        });
      });
      board.classList.add('flapped');
      rows.forEach(function (tr, i) {
        var data = pool[i] || {};
        render(tr, data, 350 + i * 160);
        $$('.flap-text .sr-only', tr).forEach(function (sr, ci) { sr.textContent = data[cols[ci]] || ''; });
      });
    };
    start();

    window.addEventListener('resize', debounce(function () {
      var next = layout();
      if (next.dest === spec.dest && next.days === spec.days && next.price === spec.price) return;
      spec = next;
      rows.forEach(function (tr, i) { render(tr, pool[(rowIndex[i] || i)] || {}, 0); });
    }, 250));

    // Rotate in more trips when there are more than six.
    var rowIndex = rows.map(function (_, i) { return i; });
    if (pool.length > rows.length && !reduceMotion) {
      var next = rows.length, r = 0;
      setInterval(function () {
        if (document.hidden) return;
        var tr = rows[r];
        rowIndex[r] = next;
        render(tr, pool[next], 0);
        $$('.flap-text .sr-only', tr).forEach(function (sr, ci) { sr.textContent = pool[next][cols[ci]] || ''; });
        next = (next + 1) % pool.length;
        r = (r + 1) % rows.length;
      }, 6500);
    }
  }

  /* ---------------------------------------------------------------- tabs */
  function initTabs() {
    $$('[data-tabs]').forEach(function (root) {
      var tabs = $$('[role=tab]', root);
      var select = function (tab, focus) {
        tabs.forEach(function (t) {
          var on = t === tab;
          t.setAttribute('aria-selected', String(on));
          t.tabIndex = on ? 0 : -1;
          var pane = document.getElementById(t.getAttribute('aria-controls'));
          if (pane) pane.hidden = !on;
        });
        if (focus) tab.focus();
      };
      tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { select(t); });
        t.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault();
            select(tabs[(i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length], true);
          }
        });
      });
      if (location.hash) {
        var wanted = tabs.filter(function (t) { return '#' + t.id.replace('tab', '').toLowerCase() === location.hash.toLowerCase(); })[0];
        if (wanted) select(wanted);
      }
    });
  }

  /* --------------------------------------------------------- flight form */
  function airportApi(q) {
    return fetch(url('/api/airports?q=' + encodeURIComponent(q))).then(function (r) { return r.json(); }).catch(function () { return []; });
  }

  function initFlightForm(form) {
    var setAirport = function (field, ap) {
      var input = $('input:not([type=hidden])', field), hidden = $('input[type=hidden]', field), sub = $('.sub', field);
      hidden.value = ap ? ap.code : '';
      input.value = ap ? ap.city + ' (' + ap.code + ')' : '';
      sub.textContent = ap ? ap.name + (ap.country ? ', ' + ap.country : '') : (field.dataset.ac === 'from' ? 'Where from?' : 'Where to?');
      field.classList.remove('is-invalid');
      field._ap = ap;
    };

    $$('[data-ac]', form).forEach(function (field) {
      var input = $('input:not([type=hidden])', field), list = $('.ac-list', field), items = [], hl = -1;
      var hidden = $('input[type=hidden]', field);
      if (hidden.value) field._ap = { code: hidden.value, city: input.value.replace(/\s*\(.*\)$/, ''), name: $('.sub', field).textContent };
      var close = function () { list.classList.remove('open'); input.setAttribute('aria-expanded', 'false'); };
      var render = function () {
        list.innerHTML = items.length ? items.map(function (a, i) {
          return '<div class="ac-item' + (i === hl ? ' hl' : '') + '" role="option" id="' + list.id + '-' + i + '" aria-selected="' + (i === hl) + '" data-i="' + i + '"><span class="ac-code">' + esc(a.code) + '</span><div><strong>' + esc(a.city) + ', ' + esc(a.country) + '</strong><small>' + esc(a.name) + '</small></div></div>';
        }).join('') : '<div class="ac-item"><small>No airports match. Try a city name or a three-letter code.</small></div>';
        list.classList.add('open');
        input.setAttribute('aria-expanded', 'true');
        if (hl >= 0) input.setAttribute('aria-activedescendant', list.id + '-' + hl);
      };
      var search = debounce(function (q) { airportApi(q).then(function (r) { items = r; hl = r.length ? 0 : -1; render(); }); }, 150);
      var choose = function (i) {
        setAirport(field, items[i]);
        close();
        if (field.dataset.ac === 'from') { var to = $('[data-ac=to] input:not([type=hidden])', form); if (to && !$('[data-ac=to] input[type=hidden]', form).value) to.focus(); }
      };
      input.addEventListener('focus', function () { input.select(); search(''); });
      input.addEventListener('input', function () { hidden.value = ''; search(input.value.replace(/\(.*\)/, '').trim()); });
      input.addEventListener('keydown', function (e) {
        if (!list.classList.contains('open')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); hl = Math.min(items.length - 1, hl + 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); hl = Math.max(0, hl - 1); render(); }
        else if (e.key === 'Enter' && hl >= 0) { e.preventDefault(); choose(hl); }
        else if (e.key === 'Escape') close();
      });
      list.addEventListener('mousedown', function (e) { var it = e.target.closest('[data-i]'); if (it) { e.preventDefault(); choose(Number(it.dataset.i)); } });
      input.addEventListener('blur', function () {
        setTimeout(function () {
          close();
          if (!hidden.value && items[0] && input.value.trim()) setAirport(field, items[0]);
          else if (field._ap && hidden.value) setAirport(field, field._ap);
        }, 150);
      });
    });

    var swap = $('[data-swap]', form);
    if (swap) swap.addEventListener('click', function () {
      var f = $('[data-ac=from]', form), t = $('[data-ac=to]', form), a = f._ap, b = t._ap;
      setAirport(f, b || null); setAirport(t, a || null);
    });

    // Trip type
    var ret = $('#returnDate', form), retField = $('[data-return]', form);
    $$('input[name=trip]', form).forEach(function (r) {
      r.addEventListener('change', function () {
        var oneway = $('input[name=trip]:checked', form).value === 'oneway';
        ret.disabled = oneway;
        retField.style.opacity = oneway ? '.45' : '';
        if (!oneway && !ret.value) { var d = new Date($('#departDate', form).value); d.setDate(d.getDate() + 7); ret.value = d.toISOString().slice(0, 10); }
      });
    });
    var dep = $('#departDate', form);
    dep.addEventListener('change', function () { ret.min = dep.value; if (ret.value && ret.value < dep.value) ret.value = dep.value; });

    // Travellers popover
    var paxField = $('[data-pax-field]', form), pop = $('.pop', paxField), paxBtn = $('.pax-btn', paxField);
    var paxText = function () {
      var a = Number($('input[name=adults]', form).value), c = Number($('input[name=children]', form).value);
      $('[data-pax-text]', form).textContent = a + ' adult' + (a > 1 ? 's' : '') + (c ? ', ' + c + ' child' + (c > 1 ? 'ren' : '') : '');
      var cabin = $('input[name=cabin]:checked', form);
      $('[data-cabin-text]', form).textContent = cabin ? cabin.nextElementSibling.textContent : 'Economy';
    };
    var openPop = function (open) { pop.classList.toggle('open', open); paxBtn.setAttribute('aria-expanded', String(open)); };
    paxBtn.addEventListener('click', function (e) { e.stopPropagation(); openPop(!pop.classList.contains('open')); });
    pop.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { openPop(false); });
    $('[data-pax-done]', pop).addEventListener('click', function () { openPop(false); paxBtn.focus(); });
    $$('[data-counter]', pop).forEach(function (c) {
      var name = c.dataset.counter, input = $('input[name=' + name + ']', form), out = $('output', c);
      $$('[data-step]', c).forEach(function (b) {
        b.addEventListener('click', function () {
          var v = Math.max(Number(c.dataset.min), Math.min(Number(c.dataset.max), Number(input.value) + Number(b.dataset.step)));
          input.value = v; out.textContent = v; paxText();
        });
      });
    });
    $$('input[name=cabin]', pop).forEach(function (r) { r.addEventListener('change', paxText); });
    paxText();

    form.addEventListener('submit', function (e) {
      var from = $('[data-ac=from] input[type=hidden]', form), to = $('[data-ac=to] input[type=hidden]', form);
      var bad = !from.value ? from : !to.value ? to : null;
      if (bad || from.value === to.value) {
        e.preventDefault();
        var field = (bad || to).closest('.field');
        field.classList.add('is-invalid');
        toast(bad ? 'Choose an airport from the list.' : 'Departure and destination must be different.', 'error');
        $('input:not([type=hidden])', field).focus();
        return;
      }
      if (form.dataset.flightForm === 'live') { e.preventDefault(); FlightResults.search(new FormData(form)); }
    });
  }

  /* ------------------------------------------------------ flight results */
  var FlightResults = (function () {
    var state = { data: null, sort: 'price' };
    var cabinLabel = function (c) { return { economy: 'Economy', premium_economy: 'Premium economy', business: 'Business', first: 'First' }[c] || 'Economy'; };
    var dur = function (m) { return m ? Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + 'm' : '—'; };
    var time = function (iso) { return iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) : '--:--'; };
    var date = function (iso) { return iso ? new Date(iso).toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short' }) : ''; };
    var stops = function (n) { return n === 0 ? 'Direct' : n + ' stop' + (n > 1 ? 's' : ''); };

    function search(fd) {
      var params = new URLSearchParams();
      ['from', 'to', 'date', 'return', 'adults', 'children', 'cabin'].forEach(function (k) { var v = fd.get(k); if (v) params.set(k, v); });
      if (fd.get('trip') === 'oneway') params.delete('return');
      history.replaceState(null, '', location.pathname + '?' + params.toString() + '&auto=1');
      var wrap = $('#flightResults');
      wrap.hidden = false;
      $('#resultsTitle').textContent = params.get('from') + ' → ' + params.get('to');
      $('#resultsSub').textContent = 'Searching airlines for the best fares…';
      $('#resultsNotice').innerHTML = '';
      $('#resultsList').innerHTML = '<div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>';
      wrap.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      fetch(url('/api/flights?' + params.toString()), { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.error || 'Search failed'); return d; }); })
        .then(function (d) {
          // Fares arrive in the provider's currency; show them in the visitor's.
          d.results.forEach(function (r) { r.price = toVisitor(r.price, r.currency); r.currency = FX.currency; });
          state.data = d; buildFilters(d.results); render();
        })
        .catch(function (err) {
          $('#resultsSub').textContent = '';
          $('#resultsList').innerHTML = '<div class="empty"><h3>We could not search right now</h3><p>' + esc(err.message) + '</p><a class="btn btn-wa" target="_blank" rel="noopener" data-wa="flight" href="' + esc(waUrl('Hello! Please find me flights from ' + params.get('from') + ' to ' + params.get('to') + ' on ' + params.get('date') + '.')) + '">' + ICON.wa + 'Ask a consultant</a></div>';
        });
    }

    function buildFilters(results) {
      var st = {}, al = {}, cur = results[0] && results[0].currency;
      results.forEach(function (r) {
        var k = Math.min(r.stops, 2);
        st[k] = Math.min(st[k] == null ? Infinity : st[k], r.price);
        al[r.airline] = Math.min(al[r.airline] == null ? Infinity : al[r.airline], r.price);
      });
      $('#stopFilters').innerHTML = Object.keys(st).sort().map(function (k) { return '<label class="check"><span><input type="checkbox" value="' + k + '" checked>' + (k == 2 ? '2+ stops' : stops(Number(k))) + '</span><small>' + money(st[k], cur) + '</small></label>'; }).join('');
      $('#airlineFilters').innerHTML = Object.keys(al).sort().map(function (a) { return '<label class="check"><span><input type="checkbox" value="' + esc(a) + '" checked>' + esc(a) + '</span><small>' + money(al[a], cur) + '</small></label>'; }).join('');
      var prices = results.map(function (r) { return r.price; });
      var range = $('#priceRange'), min = prices.length ? Math.floor(Math.min.apply(null, prices)) : 0, max = prices.length ? Math.ceil(Math.max.apply(null, prices)) : 1;
      // step 1: a coarser step snaps the maximum below the dearest fare and hides it.
      range.min = min; range.max = max; range.step = 1; range.value = max;
      $('#priceMin').textContent = money(min, cur); $('#priceVal').textContent = money(max, cur);
    }

    function filtered() {
      var d = state.data; if (!d) return [];
      var s = $$('#stopFilters input:checked').map(function (i) { return Number(i.value); });
      var a = $$('#airlineFilters input:checked').map(function (i) { return i.value; });
      var t = $$('#timeFilters input:checked').map(function (i) { return Number(i.value); });
      var max = Number($('#priceRange').value);
      return d.results.filter(function (r) {
        var slot = Math.floor(new Date(r.departAt).getHours() / 6);
        return s.indexOf(Math.min(r.stops, 2)) > -1 && a.indexOf(r.airline) > -1 && r.price <= max + 0.01 && (isNaN(slot) || t.indexOf(slot) > -1);
      });
    }

    var sorters = {
      price: function (a, b) { return a.price - b.price; },
      duration: function (a, b) { return (a.durationMin || 1e9) - (b.durationMin || 1e9); },
      depart: function (a, b) { return new Date(a.departAt) - new Date(b.departAt); }
    };

    function render() {
      var d = state.data, q = d.query, list = filtered().sort(sorters[state.sort]);
      var best = function (k) { return list.slice().sort(sorters[k])[0]; };
      $('#sortPrice').textContent = best('price') ? money(best('price').price, best('price').currency) : '—';
      $('#sortDuration').textContent = best('duration') ? dur(best('duration').durationMin) : '—';
      $('#sortDepart').textContent = best('depart') ? time(best('depart').departAt) : '—';
      var pax = q.adults + ' adult' + (q.adults > 1 ? 's' : '') + (q.children ? ', ' + q.children + ' child' + (q.children > 1 ? 'ren' : '') : '');
      $('#resultsSub').textContent = list.length + ' of ' + d.results.length + ' flights · ' + date(q.date) + (q.return ? ' to ' + date(q.return) : '') + ' · ' + pax + ' · ' + cabinLabel(q.cabin);
      $('#resultsNotice').innerHTML = d.note ? '<div class="notice">' + ICON.info + esc(d.note) + '</div>'
        : (!d.live ? '<div class="notice">' + ICON.info + 'These are sample fares. Send the flight to a consultant for a confirmed live price.</div>' : '');
      if (!list.length) {
        $('#resultsList').innerHTML = '<div class="empty"><h3>No flights match these filters</h3><p>Widen the filters, or let a consultant search every airline for you.</p><a class="btn btn-wa" target="_blank" rel="noopener" data-wa="flight" href="' + esc(waUrl(helpMsg(q))) + '">' + ICON.wa + 'Ask a consultant</a></div>';
        return;
      }
      var cheapest = Math.min.apply(null, list.map(function (r) { return r.price; }));
      $('#resultsList').innerHTML = list.map(function (r, i) {
        var msg = ['Hello! I would like to book this flight:', r.airline + ' ' + r.flightNumber, r.from + ' → ' + r.to + (r.returnTrip && q.return ? ' (return ' + q.return + ')' : ''), date(r.departAt) + ' at ' + time(r.departAt) + ' · ' + stops(r.stops) + ' · ' + dur(r.durationMin), pax + ', ' + cabinLabel(q.cabin), 'Price shown: ' + money(r.price, r.currency), 'Please confirm availability. Thank you!'].join('\n');
        return '<article class="flight" style="animation-delay:' + Math.min(i, 10) * 40 + 'ms">' +
          '<div class="flight-main"><div class="airline"><div class="airline-logo"><img src="https://pics.avs.io/96/96/' + esc(r.airlineCode) + '.png" alt="" loading="lazy" onerror="this.replaceWith(document.createTextNode(\'' + esc(r.airlineCode) + '\'))"></div><div><strong>' + esc(r.airline) + '</strong><small>' + esc(r.flightNumber) + ' · ' + esc(cabinLabel(r.cabin)) + '</small></div></div>' +
          '<div class="route"><div class="t"><b>' + time(r.departAt) + '</b><small>' + esc(r.from) + '</small></div><div class="route-line"><span>' + dur(r.durationMin) + '</span><div class="bar">' + (r.stops ? '<i></i>' : '') + '</div><span class="' + (r.stops ? '' : 'direct') + '">' + stops(r.stops) + '</span></div><div class="t"><b>' + time(r.arriveAt) + '</b><small>' + esc(r.to) + '</small></div></div></div>' +
          '<div class="flight-side"><div>' + (r.price === cheapest ? '<span class="tag tag-orange" style="margin-bottom:6px">Cheapest</span>' : '') + '<div class="price">' + money(r.price, r.currency) + '</div><small>' + (r.returnTrip ? 'Return' : 'One way') + ', total for ' + esc(pax) + '</small></div>' +
          '<a class="btn btn-wa btn-sm" target="_blank" rel="noopener" data-wa="flight" data-wa-subject="' + esc(r.from + '-' + r.to + ' ' + r.airline) + '" href="' + esc(waUrl(msg)) + '">' + ICON.wa + 'Book on WhatsApp</a>' +
          (r.bookingUrl ? '<a class="btn btn-ghost btn-sm" href="' + esc(r.bookingUrl) + '" target="_blank" rel="noopener sponsored">' + ICON.ext + 'Book online</a>' : '') + '</div></article>';
      }).join('');
    }

    function helpMsg(q) { return 'Hello! Please help me find the best flight ' + q.from + ' → ' + q.to + ' on ' + q.date + (q.return ? ', returning ' + q.return : '') + ', ' + q.adults + ' adult(s), ' + cabinLabel(q.cabin) + '.'; }

    function init() {
      var wrap = $('#flightResults');
      if (!wrap) return;
      $('#fFilters').addEventListener('input', function (e) {
        if (e.target.id === 'priceRange') $('#priceVal').textContent = money(Number(e.target.value), state.data && state.data.results[0] && state.data.results[0].currency);
        if (state.data) render();
      });
      $$('.sort-tab').forEach(function (t) {
        t.addEventListener('click', function () {
          state.sort = t.dataset.sort;
          $$('.sort-tab').forEach(function (x) { x.setAttribute('aria-pressed', String(x === t)); });
          if (state.data) render();
        });
      });
      var helpBtn = $('#flightHelp');
      if (helpBtn) helpBtn.addEventListener('click', function (e) { if (state.data) { helpBtn.href = waUrl(helpMsg(state.data.query)); } });
      var form = $('form[data-flight-form=live]');
      var params = new URLSearchParams(location.search);
      if (form && params.get('auto') === '1' && params.get('from') && params.get('to')) search(new FormData(form));
    }
    return { init: init, search: search };
  })();

  /* --------------------------------------------------------------- WhatsApp */
  function waUrl(message) {
    return OK.wa ? 'https://wa.me/' + OK.wa + '?text=' + encodeURIComponent(message) : url('/contact');
  }

  function logWhatsApp(el) {
    var href = el.getAttribute('href') || '';
    var text = '';
    try { text = decodeURIComponent((href.split('text=')[1] || '').replace(/\+/g, ' ')); } catch (e) { text = ''; }
    var body = JSON.stringify({ type: el.dataset.wa || 'contact', subject: el.dataset.waSubject || '', package_id: el.dataset.waPackage || '', message: text, page: location.pathname });
    try {
      if (navigator.sendBeacon) navigator.sendBeacon(url('/api/whatsapp'), new Blob([body], { type: 'application/json' }));
      else fetch(url('/api/whatsapp'), { method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: body });
    } catch (e) { /* logging must never block the visitor */ }
    if (window.gtag) window.gtag('event', 'whatsapp_click', { topic: el.dataset.wa || 'contact' });
    if (window.fbq) window.fbq('track', 'Contact');
  }

  function initWhatsApp() {
    document.addEventListener('click', function (e) {
      var el = e.target.closest('a[data-wa]');
      if (el && /wa\.me/.test(el.href)) logWhatsApp(el);
    });
    var fab = $('#waFab'), panel = $('#waPanel');
    if (!fab || !panel) return;
    var set = function (open) {
      panel.classList.toggle('open', open);
      panel.setAttribute('aria-hidden', String(!open));
      fab.setAttribute('aria-expanded', String(open));
      if (open) setTimeout(function () { var a = $('.wa-topics a', panel); if (a) a.focus(); }, 150);
    };
    fab.addEventListener('click', function () { set(!panel.classList.contains('open')); });
    $('#waClose').addEventListener('click', function () { set(false); fab.focus(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('open')) { set(false); fab.focus(); } });
  }

  /* ------------------------------------------------------------ AJAX forms */
  function initForms() {
    $$('form[data-ajax]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var kind = form.dataset.ajax;
        $$('.is-invalid', form).forEach(function (x) { x.classList.remove('is-invalid'); });
        $$('.field-error', form).forEach(function (x) { x.remove(); });
        if (kind === 'enquiry') {
          var missing = $$('[required]', form).filter(function (i) { return !i.value.trim(); });
          if (missing.length) {
            missing.forEach(function (i) { (i.closest('.field') || i).classList.add('is-invalid'); });
            missing[0].focus();
            toast('Fill in the highlighted fields.', 'error');
            return;
          }
        }
        var btn = $('button[type=submit]', form);
        btn.classList.add('is-loading');
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' } })
          .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            btn.classList.remove('is-loading');
            if (!res.ok) {
              var fields = res.d.fields || {};
              Object.keys(fields).forEach(function (name) {
                var inp = form.querySelector('[name="' + name + '"]');
                if (!inp) return;
                (inp.closest('.field') || inp).classList.add('is-invalid');
                if (inp.classList.contains('inp')) inp.insertAdjacentHTML('afterend', '<span class="field-error">' + esc(fields[name]) + '</span>');
              });
              toast(res.d.error || 'Something went wrong. Try again.', 'error');
              return;
            }
            if (kind === 'subscribe') { form.reset(); toast(res.d.message || 'Subscribed.'); return; }
            showSuccess(form, res.d);
            if (window.gtag) window.gtag('event', 'generate_lead', { type: (form.querySelector('[name=type]') || {}).value });
            if (window.fbq) window.fbq('track', 'Lead');
          })
          .catch(function () { btn.classList.remove('is-loading'); toast('No connection. Check your internet and try again.', 'error'); });
      });
    });
  }

  function showSuccess(form, d) {
    var box = document.createElement('div');
    box.className = 'form-success';
    box.setAttribute('role', 'status');
    box.innerHTML = '<div class="tick">' + ICON.check + '</div><h3 class="h-s" style="margin-bottom:.2em">Request received</h3>' +
      '<p class="muted" style="margin:0">Your reference</p><div class="ref">' + esc(d.ref) + '</div>' +
      '<p class="muted small" style="max-width:40ch;margin:0 auto 16px">A consultant will contact you shortly. For a faster reply, send your reference on WhatsApp.</p>' +
      (d.whatsapp ? '<a class="btn btn-wa" target="_blank" rel="noopener" href="' + esc(d.whatsapp) + '">' + ICON.wa + 'Continue on WhatsApp</a>' : '');
    form.replaceWith(box);
    box.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
  }

  /* ----------------------------------------------------------- price total */
  function initTotals() {
    $$('[data-total]').forEach(function (line) {
      var form = line.closest('form'), price = Number(line.dataset.price);
      var calc = function () {
        var a = Math.max(1, Number(($('[data-pax]', form) || {}).value) || 1), c = Math.max(0, Number(($('[data-pax-child]', form) || {}).value) || 0);
        $('b', line).textContent = money(price * (a + c));
      };
      form.addEventListener('input', calc);
      calc();
    });
  }

  /* ------------------------------------------------------------ carousels */
  function initScrollers() {
    $$('[data-scroll]').forEach(function (b) {
      b.addEventListener('click', function () {
        var el = $(b.dataset.scroll);
        var card = el.firstElementChild;
        el.scrollBy({ left: Number(b.dataset.dir) * (card ? card.offsetWidth + 22 : 400), behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    });
  }

  /* -------------------------------------------- destination image peek */
  function initPeek() {
    var list = $('[data-peek]');
    if (!list || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    var peek = document.createElement('div');
    peek.className = 'dest-peek';
    peek.innerHTML = '<img alt="">';
    document.body.appendChild(peek);
    var img = $('img', peek), x = 0, y = 0, cx = 0, cy = 0, raf = null;
    var loop = function () {
      cx += (x - cx) * 0.18; cy += (y - cy) * 0.18;
      peek.style.left = cx + 'px'; peek.style.top = cy + 'px';
      raf = Math.abs(x - cx) + Math.abs(y - cy) > 0.5 ? requestAnimationFrame(loop) : null;
    };
    list.addEventListener('mousemove', function (e) { x = e.clientX + 170; y = e.clientY; if (!raf) raf = requestAnimationFrame(loop); });
    $$('a[data-img]', list).forEach(function (a) {
      a.addEventListener('mouseenter', function (e) {
        if (img.getAttribute('src') !== a.dataset.img) img.src = a.dataset.img;
        if (!peek.classList.contains('show')) { cx = x = e.clientX + 170; cy = y = e.clientY; }
        peek.classList.add('show');
      });
    });
    list.addEventListener('mouseleave', function () { peek.classList.remove('show'); });
  }

  /* -------------------------------------------------------------- lightbox */
  function initLightbox() {
    var links = $$('[data-lightbox]');
    if (!links.length) return;
    var box = document.createElement('div');
    box.className = 'lightbox';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Photo viewer');
    box.innerHTML = '<img alt=""><button class="lb-close" aria-label="Close">✕</button><button class="lb-prev" aria-label="Previous photo">‹</button><button class="lb-next" aria-label="Next photo">›</button>';
    document.body.appendChild(box);
    var i = 0, last = null;
    var show = function (n) { i = (n + links.length) % links.length; $('img', box).src = links[i].href; $('img', box).alt = ($('img', links[i]) || {}).alt || ''; };
    var close = function () { box.classList.remove('open'); document.body.style.overflow = ''; if (last) last.focus(); };
    links.forEach(function (a, n) { a.addEventListener('click', function (e) { e.preventDefault(); last = a; show(n); box.classList.add('open'); document.body.style.overflow = 'hidden'; $('.lb-close', box).focus(); }); });
    $('.lb-close', box).addEventListener('click', close);
    $('.lb-prev', box).addEventListener('click', function () { show(i - 1); });
    $('.lb-next', box).addEventListener('click', function () { show(i + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) {
      if (!box.classList.contains('open')) return;
      if (e.key === 'Escape') close(); else if (e.key === 'ArrowLeft') show(i - 1); else if (e.key === 'ArrowRight') show(i + 1);
    });
  }

  /* ------------------------------------------------------- listing filters */
  function initListing() {
    var toggle = $('[data-filters-toggle]'), panel = $('#filters');
    if (toggle && panel) toggle.addEventListener('click', function () { var o = panel.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(o)); });
    $$('select[data-autosubmit]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
  }

  /* -------------------------------------------------------------- polish */
  function initPolish() {
    document.documentElement.classList.add('fx');

    // Header steps aside on scroll down, returns on scroll up.
    var header = $('#siteHeader'), lastY = window.scrollY, ticking = false;
    if (header) {
      window.addEventListener('scroll', function () {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () {
          var y = window.scrollY, busy = document.body.classList.contains('menu-open') || $('details[data-cur][open]');
          if (!busy && y > 420 && y > lastY + 4) header.classList.add('away');
          else if (y < lastY - 4 || y < 420) header.classList.remove('away');
          lastY = y; ticking = false;
        });
      }, { passive: true });
      // Keyboard users tabbing into the header always see it.
      header.addEventListener('focusin', function () { header.classList.remove('away'); });
    }

    // Images fade in once loaded.
    $$('.pkg-media img, .dest-card img, .post-card img, .gallery img, .umrah-visual img, .dest-thumb img').forEach(function (img) {
      img.classList.add('fade');
      if (img.complete && img.naturalWidth) img.classList.add('loaded');
      else {
        img.addEventListener('load', function () { img.classList.add('loaded'); });
        img.addEventListener('error', function () { img.classList.add('loaded'); });
      }
    });

    if (reduceMotion || !('IntersectionObserver' in window)) return;

    // Reveal headings and photos as they enter the viewport (once).
    $$('.sec-head h2, .page-head:not(.has-img) h1, .faq-layout h2, .umrah-band h2, .cta-band h2').forEach(function (el) { el.classList.add('rv-h'); });
    $$('.pkg-media, .post-card .media, .umrah-visual, .dest-card').forEach(function (el) { el.classList.add('rv-img'); });
    // A fully clipped element has no visible area, so the observer would never report it:
    // headings are watched through their parent instead.
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        var el = en.target._rvTarget || en.target;
        // Stagger siblings in the same grid slightly.
        var idx = Array.prototype.indexOf.call(el.parentNode.parentNode.children, el.parentNode);
        el.style.transitionDelay = el.classList.contains('rv-img') ? Math.min(Math.max(idx, 0), 4) * 70 + 'ms' : '';
        el.classList.add('in');
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    $$('.rv-img').forEach(function (el) { io.observe(el); });
    $$('.rv-h').forEach(function (el) { el.parentNode._rvTarget = el; io.observe(el.parentNode); });

    // Numbers count up the first time they are seen ("15,000+" keeps its suffix and commas).
    var counter = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        counter.unobserve(en.target);
        var el = en.target, text = el.textContent, m = text.match(/[\d,]+(\.\d+)?/);
        if (!m) return;
        var target = parseFloat(m[0].replace(/,/g, '')), hasComma = m[0].indexOf(',') > -1, start = null;
        if (!target || target < 10) return;
        var step = function (now) {
          if (!start) start = now;
          var p = Math.min(1, (now - start) / 1400), v = Math.round(target * (1 - Math.pow(1 - p, 3)));
          el.textContent = text.replace(m[0], hasComma ? v.toLocaleString('en-US') : String(v));
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    $$('.stat b').forEach(function (el) { counter.observe(el); });
  }

  /* ------------------------------------------------------------------ boot */
  function boot() {
    initPolish();
    initHeader();
    initHero();
    initBoard();
    initTabs();
    $$('form[data-flight-form]').forEach(initFlightForm);
    FlightResults.init();
    initWhatsApp();
    initForms();
    initTotals();
    initScrollers();
    initPeek();
    initLightbox();
    initListing();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
