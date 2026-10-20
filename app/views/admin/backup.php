<?php /** @var array $counts @var int $uploadsSize */ $GLOBALS['_title'] = 'Backup'; ?>
<div class="page-top"><div><h1>Backup</h1><p>Keep a copy of your data off the server.</p></div></div>
<div class="grid g2">
  <div class="card">
    <div class="card-head"><h2>Download your data</h2></div>
    <p>One file with every package, destination, article, lead, note, subscriber and setting. Download it regularly — for example every Monday — and keep it somewhere safe.</p>
    <a class="btn btn-dark" href="<?= e(url('/admin/backup/download')) ?>"><?= icon('download') ?>Download backup (.json)</a>
    <p class="help" style="margin-top:12px">Passwords are stored as one-way hashes and cannot be read from the file.</p>
  </div>
  <div class="card">
    <div class="card-head"><h2>Full server backups</h2></div>
    <p>Hostinger keeps automatic backups of files and databases. To restore or download one: hPanel → Files → Backups.</p>
    <p>Your uploaded images live in the <code>uploads/</code> folder (<?= e(number_format($uploadsSize / 1048576, 1)) ?> MB). Include it when you copy the site.</p>
  </div>
</div>
<div class="card" style="margin-top:16px">
  <div class="card-head"><h2>What is stored</h2></div>
  <div class="grid g4">
    <?php foreach ($counts as $t => $n): ?><div><b style="font-size:20px"><?= number_format($n) ?></b><br><span class="muted small"><?= e(ucfirst(str_replace('_', ' ', $t))) ?></span></div><?php endforeach; ?>
  </div>
</div>
