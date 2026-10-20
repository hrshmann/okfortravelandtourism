<?php /** @var array $checks @var array $errors @var array $values */ ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Install — OK Travel &amp; Tourism</title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head><body>
<div class="install">
  <header><img src="<?= e(url('/assets/img/logo.png')) ?>" alt="OK Travel &amp; Tourism" width="160" height="54"><div><h1 style="margin:0;font-size:24px">Set up your website</h1><p class="muted" style="margin:0">This takes about two minutes and only runs once.</p></div></header>

  <?php foreach ($errors as $err): ?><div class="alert error"><?= icon('alert') ?><span><?= e($err) ?></span></div><?php endforeach; ?>

  <div class="card">
    <div class="card-head"><h2>1. Server check</h2></div>
    <ul class="checks">
      <?php foreach ($checks as $c): ?>
        <li><span class="<?= $c['ok'] ? 'ok' : (!empty($c['optional']) ? 'opt' : 'bad') ?>"><?= icon($c['ok'] ? 'checkcircle' : 'alert') ?></span><?= e($c['label']) ?><?= !$c['ok'] && !empty($c['optional']) ? ' <span class="muted">(optional)</span>' : '' ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <form method="post" class="card" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-section"><h3>2. Database</h3><p>On Hostinger: hPanel → Databases → MySQL Databases. Create a database and user, then copy the details here.</p></div>
      <div class="fld c4">
        <span class="label">Database type</span>
        <div style="display:flex;gap:18px;flex-wrap:wrap">
          <label class="switch"><input type="radio" name="driver" value="mysql"<?= $values['driver'] === 'mysql' ? ' checked' : '' ?><?= extension_loaded('pdo_mysql') ? '' : ' disabled' ?>><i></i>MySQL / MariaDB (recommended)</label>
          <label class="switch"><input type="radio" name="driver" value="sqlite"<?= $values['driver'] === 'sqlite' ? ' checked' : '' ?><?= extension_loaded('pdo_sqlite') ? '' : ' disabled' ?>><i></i>SQLite (no setup, small sites)</label>
        </div>
      </div>
      <div class="fld c2" data-mysql><label for="db_host">Host</label><input class="inp" id="db_host" name="db_host" value="<?= e($values['db_host']) ?>"><span class="help">Usually <code>localhost</code> on Hostinger.</span></div>
      <div class="fld c2" data-mysql><label for="db_port">Port</label><input class="inp" id="db_port" name="db_port" value="<?= e($values['db_port']) ?>" inputmode="numeric"></div>
      <div class="fld c2" data-mysql><label for="db_name">Database name</label><input class="inp" id="db_name" name="db_name" value="<?= e($values['db_name']) ?>" placeholder="u123456789_oktravel"></div>
      <div class="fld c2" data-mysql><label for="db_user">Database user</label><input class="inp" id="db_user" name="db_user" value="<?= e($values['db_user']) ?>" placeholder="u123456789_admin"></div>
      <div class="fld c4" data-mysql><label for="db_pass">Database password</label><input class="inp" id="db_pass" name="db_pass" type="password"></div>

      <div class="form-section"><h3>3. Business</h3></div>
      <div class="fld c2"><label for="site_name">Website name</label><input class="inp" id="site_name" name="site_name" value="<?= e($values['site_name']) ?>"></div>
      <div class="fld c2"><label for="whatsapp">WhatsApp number</label><input class="inp" id="whatsapp" name="whatsapp" value="<?= e($values['whatsapp']) ?>" placeholder="971501234567" inputmode="tel"><span class="help">International format, digits only.</span></div>
      <div class="fld c2"><label for="timezone">Time zone</label>
        <select class="inp" id="timezone" name="timezone"><?php foreach (['Asia/Dubai', 'Asia/Riyadh', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Qatar', 'Asia/Muscat', 'Asia/Kuwait', 'Asia/Bahrain', 'Europe/London', 'Europe/Istanbul', 'UTC'] as $tz): ?><option<?= $values['timezone'] === $tz ? ' selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?></select></div>

      <div class="form-section"><h3>4. Your administrator account</h3></div>
      <div class="fld c2"><label for="admin_name">Your name</label><input class="inp" id="admin_name" name="admin_name" value="<?= e($values['admin_name']) ?>" required autocomplete="name"></div>
      <div class="fld c2"><label for="admin_email">Email</label><input class="inp" id="admin_email" name="admin_email" type="email" value="<?= e($values['admin_email']) ?>" required autocomplete="email"></div>
      <div class="fld c4"><label for="admin_password">Password</label><input class="inp" id="admin_password" name="admin_password" type="password" required minlength="10" autocomplete="new-password"><span class="help">At least 10 characters, with letters and numbers.</span></div>
    </div>
    <div style="margin-top:22px;display:flex;justify-content:flex-end"><button class="btn btn-dark" type="submit">Install website</button></div>
  </form>
</div>
<script>
(function () {
  var radios = document.querySelectorAll('input[name=driver]');
  var sync = function () {
    var mysql = document.querySelector('input[name=driver]:checked').value === 'mysql';
    document.querySelectorAll('[data-mysql]').forEach(function (el) { el.style.display = mysql ? '' : 'none'; });
  };
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
</body></html>
