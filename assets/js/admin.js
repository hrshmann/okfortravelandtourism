/* OK Travel & Tourism — admin panel behaviour */
(function () {
  'use strict';

  var A = window.ADMIN || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var url = function (p) { return (A.base || '') + p; };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  function toast(msg, type) {
    var el = document.createElement('div');
    el.className = 'toast' + (type ? ' ' + type : '');
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.textContent = msg;
    $('#toasts').appendChild(el);
    setTimeout(function () { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; }, 3800);
    setTimeout(function () { el.remove(); }, 4200);
  }

  function post(path, data, json) {
    var opts = { method: 'POST', headers: { 'X-CSRF-Token': A.csrf, 'X-Requested-With': 'fetch', Accept: 'application/json' } };
    if (json) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
    else { var fd = data instanceof FormData ? data : new FormData(); if (!(data instanceof FormData)) Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); }); fd.append('_csrf', A.csrf); opts.body = fd; }
    return fetch(url(path), opts).then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok) throw new Error(d.error || 'Request failed (' + r.status + ')'); return d; }); });
  }

  /* ---------------------------------------------------------------- shell */
  function initShell() {
    var side = $('#side'), btn = $('[data-side-toggle]');
    if (btn) btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = side.classList.toggle('open');
      btn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', function (e) { if (side && side.classList.contains('open') && !side.contains(e.target)) side.classList.remove('open'); });

    document.addEventListener('click', function (e) {
      var c = e.target.closest('[data-confirm]');
      if (c && !window.confirm(c.dataset.confirm)) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    $$('[data-copy]').forEach(function (b) {
      b.addEventListener('click', function () {
        var text = b.dataset.copy;
        (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () { toast('Copied: ' + text); }, function () { window.prompt('Copy this path:', text); });
      });
    });
  }

  /* ------------------------------------------------------------ list page */
  function initList() {
    var table = $('table[data-resource]');
    if (!table) return;
    var res = table.dataset.resource;

    $$('input[data-toggle]', table).forEach(function (cb) {
      cb.addEventListener('change', function () {
        var id = cb.closest('tr').dataset.id;
        post('/admin/' + res + '/' + id + '/toggle', { field: cb.dataset.toggle, value: cb.checked ? '1' : '0' })
          .then(function () { toast('Saved.'); })
          .catch(function (err) { cb.checked = !cb.checked; toast(err.message, 'error'); });
      });
    });

    if (!table.hasAttribute('data-sortable')) return;
    var tbody = $('tbody', table), dragging = null;
    tbody.addEventListener('dragstart', function (e) {
      dragging = e.target.closest('tr');
      if (!dragging) return;
      dragging.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragging.dataset.id);
    });
    tbody.addEventListener('dragover', function (e) {
      e.preventDefault();
      var tr = e.target.closest('tr');
      $$('.drop-before', tbody).forEach(function (x) { x.classList.remove('drop-before'); });
      if (tr && tr !== dragging) tr.classList.add('drop-before');
    });
    tbody.addEventListener('drop', function (e) {
      e.preventDefault();
      var tr = e.target.closest('tr');
      $$('.drop-before', tbody).forEach(function (x) { x.classList.remove('drop-before'); });
      if (!tr || !dragging || tr === dragging) return;
      var rect = tr.getBoundingClientRect();
      tbody.insertBefore(dragging, e.clientY > rect.top + rect.height / 2 ? tr.nextSibling : tr);
      save();
    });
    tbody.addEventListener('dragend', function () { if (dragging) dragging.classList.remove('dragging'); dragging = null; });
    function save() {
      var ids = $$('tr[data-id]', tbody).map(function (tr) { return Number(tr.dataset.id); });
      post('/admin/' + res + '/reorder?offset=' + (table.dataset.offset || 0), { ids: ids }, true)
        .then(function () { toast('New order saved.'); })
        .catch(function (err) { toast(err.message, 'error'); });
    }
  }

  /* ---------------------------------------------------------- media modal */
  var Media = (function () {
    var modal, grid, more, useBtn, page = 1, pages = 1, multi = false, selected = [], onPick = null;
    function init() {
      modal = $('#mediaModal');
      if (!modal) return;
      grid = $('[data-media-grid]', modal); more = $('[data-media-more]', modal); useBtn = $('[data-media-use]', modal);
      $$('[data-modal-close]', modal).forEach(function (b) { b.addEventListener('click', close); });
      modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) close(); });
      more.addEventListener('click', function () { load(page + 1); });
      grid.addEventListener('click', function (e) {
        var b = e.target.closest('.media-item');
        if (!b) return;
        var path = b.dataset.path;
        if (multi) {
          var i = selected.indexOf(path);
          if (i > -1) selected.splice(i, 1); else selected.push(path);
          b.classList.toggle('sel', i === -1);
        } else {
          selected = [path];
          $$('.media-item', grid).forEach(function (x) { x.classList.toggle('sel', x === b); });
        }
        useBtn.disabled = !selected.length;
        useBtn.textContent = multi && selected.length > 1 ? 'Use ' + selected.length + ' images' : 'Use image';
      });
      grid.addEventListener('dblclick', function (e) { if (!multi && e.target.closest('.media-item')) use(); });
      useBtn.addEventListener('click', use);
      bindUpload($('[data-dropzone]', modal), $('[data-upload-input]', modal), function (items) {
        items.reverse().forEach(function (m) { grid.insertAdjacentHTML('afterbegin', tile(m)); });
        var first = $('.media-item', grid);
        if (first && !multi) first.click();
      });
    }
    function tile(m) { return '<button type="button" class="media-item" data-path="' + esc(m.path) + '"><img src="' + esc(m.url) + '" alt="" loading="lazy"><span class="meta">' + esc(m.original_name || '') + '</span></button>'; }
    function load(p) {
      return fetch(url('/admin/media.json?page=' + p), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
        page = d.page; pages = d.pages;
        if (p === 1) grid.innerHTML = '';
        grid.insertAdjacentHTML('beforeend', d.items.map(tile).join(''));
        if (!d.items.length && p === 1) grid.innerHTML = '<p class="muted" style="grid-column:1/-1">No images yet — upload one above.</p>';
        more.hidden = page >= pages;
      });
    }
    function open(opts) {
      multi = !!opts.multi; onPick = opts.onPick; selected = [];
      useBtn.disabled = true; useBtn.textContent = 'Use image';
      $('[data-media-hint]', modal).textContent = multi ? 'Click images to select several.' : 'Click an image to select it, or double-click to use it.';
      modal.classList.add('open');
      load(1);
      setTimeout(function () { $('[data-modal-close]', modal).focus(); }, 50);
    }
    function close() { modal.classList.remove('open'); }
    function use() { if (!selected.length) return; var s = selected.slice(); close(); onPick(multi ? s : s[0]); }
    return { init: init, open: open };
  })();

  function mediaUrl(path) { return /^(https?:)?\/\//.test(path) ? path : url('/' + path.replace(/^\//, '')); }

  function bindUpload(zone, input, done) {
    if (!zone || !input) return;
    var send = function (files) {
      if (!files.length) return;
      var fd = new FormData();
      Array.prototype.forEach.call(files, function (f) { fd.append('files[]', f); });
      zone.classList.add('over');
      var label = $('strong', zone), was = label.textContent;
      label.textContent = 'Uploading ' + files.length + ' image' + (files.length > 1 ? 's' : '') + '…';
      post('/admin/media/upload', fd).then(function (d) {
        if (d.errors && d.errors.length) toast(d.errors.join(' '), 'error');
        if (d.items.length) toast(d.items.length + ' image' + (d.items.length > 1 ? 's' : '') + ' uploaded.');
        done(d.items);
      }).catch(function (err) { toast(err.message, 'error'); })
        .then(function () { zone.classList.remove('over'); label.textContent = was; input.value = ''; });
    };
    input.addEventListener('change', function () { send(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('over'); }); });
    zone.addEventListener('drop', function (e) { send(e.dataTransfer.files); });
  }

  /* --------------------------------------------------------- form fields */
  function initFields() {
    // Media library page: upload and refresh.
    var pageZone = $('form[data-upload-form] [data-dropzone]');
    if (pageZone) bindUpload(pageZone, $('[data-upload-input]', pageZone), function (items) { if (items.length) location.reload(); });

    // Single image fields.
    $$('[data-image-field]').forEach(function (f) {
      var input = $('input', f), prev = $('.preview', f);
      var set = function (v) { input.value = v; prev.src = v ? mediaUrl(v) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='; input.dispatchEvent(new Event('input', { bubbles: true })); };
      input.addEventListener('change', function () { prev.src = input.value ? mediaUrl(input.value) : ''; });
      $('[data-pick-image]', f).addEventListener('click', function () { Media.open({ onPick: set }); });
      var clear = $('[data-clear-image]', f);
      if (clear) clear.addEventListener('click', function () { set(''); });
    });

    // Gallery fields.
    $$('[data-gallery]').forEach(function (g) {
      var name = g.dataset.name, add = $('[data-pick-gallery]', g);
      g.addEventListener('click', function (e) { var r = e.target.closest('[data-remove]'); if (r) { r.parentNode.remove(); markDirty(g); } });
      add.addEventListener('click', function () {
        Media.open({ multi: true, onPick: function (paths) {
          paths.forEach(function (p) {
            add.insertAdjacentHTML('beforebegin', '<div class="g-item"><img src="' + esc(mediaUrl(p)) + '" alt=""><input type="hidden" name="' + esc(name) + '" value="' + esc(p) + '"><button type="button" data-remove aria-label="Remove photo">×</button></div>');
          });
          markDirty(g);
        } });
      });
    });

    // Itinerary repeater.
    $$('[data-repeater]').forEach(function (r) {
      var renumber = function () { $$('.rep-item', r).forEach(function (it, i) { $('[data-n]', it).textContent = i + 1; }); };
      r.addEventListener('click', function (e) {
        var item = e.target.closest('.rep-item');
        if (e.target.closest('[data-add]')) {
          var tpl = $('.rep-item', r).cloneNode(true);
          $$('input, textarea', tpl).forEach(function (x) { x.value = ''; });
          e.target.closest('[data-add]').before(tpl);
          $('input', tpl).focus();
        } else if (item && e.target.closest('[data-remove]')) {
          if ($$('.rep-item', r).length > 1) item.remove(); else $$('input, textarea', item).forEach(function (x) { x.value = ''; });
        } else if (item && e.target.closest('[data-up]') && item.previousElementSibling) {
          item.parentNode.insertBefore(item, item.previousElementSibling);
        } else if (item && e.target.closest('[data-down]') && item.nextElementSibling && item.nextElementSibling.classList.contains('rep-item')) {
          item.parentNode.insertBefore(item.nextElementSibling, item);
        } else return;
        renumber(); markDirty(r);
      });
    });

    // URL slugs follow the title until edited by hand.
    $$('[data-slug-from]').forEach(function (slug) {
      var src = document.getElementById(slug.dataset.slugFrom);
      if (!src) return;
      var locked = slug.hasAttribute('data-locked');
      var make = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };
      src.addEventListener('input', function () { if (!locked) slug.value = make(src.value); });
      slug.addEventListener('input', function () { locked = true; });
      slug.addEventListener('blur', function () { slug.value = make(slug.value); });
    });

    // Character counters for SEO fields.
    $$('[data-counter]').forEach(function (inp) {
      var max = Number(inp.dataset.counter), out = document.createElement('span');
      out.className = 'counter';
      inp.insertAdjacentElement('afterend', out);
      var upd = function () { var n = inp.value.length; out.textContent = n + ' / ' + max + ' recommended'; out.classList.toggle('over', n > max); };
      inp.addEventListener('input', upd); upd();
    });
  }

  /* ------------------------------------------------------- rich text editor */
  function initRte() {
    $$('[data-rte]').forEach(function (rte) {
      var body = $('.rte-body', rte), ta = $('textarea', rte);
      var sync = function () { if (!rte.classList.contains('source')) ta.value = body.innerHTML.trim() === '<br>' ? '' : body.innerHTML; };
      body.addEventListener('input', function () { sync(); markDirty(rte); });
      body.addEventListener('paste', function (e) {
        // Paste as clean text paragraphs: Word and web pages bring hidden styles.
        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        var html = text.split(/\n{2,}/).map(function (p) { return '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>'; }).join('');
        document.execCommand('insertHTML', false, html);
      });
      $('.rte-bar', rte).addEventListener('click', function (e) {
        var b = e.target.closest('button[data-cmd]');
        if (!b) return;
        var cmd = b.dataset.cmd;
        if (cmd === 'source') {
          if (rte.classList.toggle('source')) { ta.value = body.innerHTML; ta.focus(); }
          else { body.innerHTML = ta.value; sync(); }
          return;
        }
        body.focus();
        if (cmd === 'createLink') {
          var href = window.prompt('Link address (https://…, /packages, mailto:…)');
          if (href) document.execCommand('createLink', false, href);
        } else if (cmd === 'insertImage') {
          var sel = window.getSelection(), range = sel.rangeCount ? sel.getRangeAt(0) : null;
          Media.open({ onPick: function (p) {
            body.focus();
            if (range) { sel.removeAllRanges(); sel.addRange(range); }
            document.execCommand('insertImage', false, mediaUrl(p));
            sync();
          } });
          return;
        } else {
          document.execCommand(cmd, false, b.dataset.arg ? '<' + b.dataset.arg + '>' : null);
        }
        sync(); markDirty(rte);
      });
      ta.addEventListener('input', function () { markDirty(rte); });
      var form = rte.closest('form');
      if (form) form.addEventListener('submit', function () { if (rte.classList.contains('source')) body.innerHTML = ta.value; else sync(); });
    });
  }

  /* ------------------------------------------------------ unsaved changes */
  var dirty = false;
  function markDirty(el) {
    var form = el && el.closest ? el.closest('form[data-dirty]') : null;
    if (!form) return;
    dirty = true;
    var note = $('[data-dirty-note]', form);
    if (note) { note.textContent = 'Unsaved changes'; note.style.color = 'var(--amber)'; }
  }
  function initDirty() {
    $$('form[data-dirty]').forEach(function (form) {
      form.addEventListener('input', function (e) { markDirty(e.target); });
      form.addEventListener('change', function (e) { markDirty(e.target); });
      form.addEventListener('submit', function () { dirty = false; });
    });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ------------------------------------------------------------ settings */
  function initSettings() {
    var form = $('form[data-settings]');
    if (!form) return;
    var tabs = $$('[role=tab]', form);
    var show = function (name) {
      tabs.forEach(function (t) {
        var on = t.dataset.tab === name;
        t.setAttribute('aria-selected', String(on));
        document.getElementById('panel-' + t.dataset.tab).hidden = !on;
      });
      form.elements._tab.value = '#' + name;
      history.replaceState(null, '', '#' + name);
    };
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
    var hash = location.hash.replace('#', '');
    if (hash && document.getElementById('panel-' + hash)) show(hash);

    var test = $('[data-test-email]', form);
    if (test) test.addEventListener('click', function () {
      test.disabled = true;
      post('/admin/settings/test-email', {}).then(function (d) { toast(d.message); }).catch(function (err) { toast(err.message, 'error'); }).then(function () { test.disabled = false; });
    });
    var fx = $('[data-fx-refresh]', form);
    if (fx) fx.addEventListener('click', function () {
      if (dirty && !window.confirm('Updating rates reloads the page and discards unsaved changes. Continue?')) return;
      fx.disabled = true;
      post('/admin/settings/fx-refresh', {}).then(function (d) { toast(d.message); dirty = false; setTimeout(function () { location.reload(); }, 600); })
        .catch(function (err) { toast(err.message, 'error'); fx.disabled = false; });
    });
    var wa = $('[data-test-wa]', form);
    if (wa) wa.addEventListener('click', function (e) {
      e.preventDefault();
      var n = form.elements.whatsapp.value.replace(/\D/g, '');
      if (n.length < 8) { toast('Enter the number with country code first.', 'error'); return; }
      window.open('https://wa.me/' + n + '?text=' + encodeURIComponent('Test message from the website settings.'), '_blank', 'noopener');
    });
  }

  /* --------------------------------------------------------------- leads */
  function initLeads() {
    var pipe = $('[data-pipeline]');
    if (pipe) {
      var input = pipe.parentNode.querySelector('input[name=status]');
      pipe.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-value]');
        if (!b) return;
        $$('button', pipe).forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
        input.value = b.dataset.value;
        markDirty(pipe);
      });
    }
    var bulk = $('form[data-bulk]');
    if (bulk) {
      var bar = $('[data-bulk-bar]', bulk), all = $('[data-check-all]', bulk);
      var upd = function () {
        var n = $$('input[name="ids[]"]:checked', bulk).length;
        if (bar) { bar.hidden = !n; $('[data-bulk-count]', bar).textContent = n + ' selected'; }
      };
      if (all) all.addEventListener('change', function () { $$('input[name="ids[]"]', bulk).forEach(function (c) { c.checked = all.checked; }); upd(); });
      bulk.addEventListener('change', upd);
    }
  }

  function boot() {
    initShell();
    Media.init();
    initList();
    initFields();
    initRte();
    initDirty();
    initSettings();
    initLeads();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
