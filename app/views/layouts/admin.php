<?php
/** @var string $content @var array $u */
$u = $u ?? auth_user();
$title = $title ?? ($GLOBALS['_title'] ?? 'Admin');
$nav = [
    ['', [
        ['/admin', 'Dashboard', 'grid', 'agent'],
        ['/admin/leads', 'Leads & bookings', 'inbox', 'agent', new_leads_count()],
    ]],
    ['Website content', [
        ['/admin/packages', 'Packages', 'palm', 'editor'],
        ['/admin/destinations', 'Destinations', 'globe', 'editor'],
        ['/admin/visas', 'Visas', 'passport', 'editor'],
        ['/admin/posts', 'Travel journal', 'book', 'editor'],
        ['/admin/testimonials', 'Reviews', 'quote', 'editor'],
        ['/admin/faqs', 'FAQs', 'help', 'editor'],
        ['/admin/pages', 'Pages', 'file', 'editor'],
        ['/admin/media', 'Media library', 'image', 'editor'],
    ]],
    ['Business', [
        ['/admin/subscribers', 'Subscribers', 'mail', 'editor'],
        ['/admin/users', 'Team', 'users', 'admin'],
        ['/admin/settings', 'Settings', 'settings', 'admin'],
        ['/admin/activity', 'Activity log', 'activity', 'admin'],
        ['/admin/backup', 'Backup', 'database', 'admin'],
    ]],
];
$path = current_path();
$isCurrent = fn($href) => $href === '/admin' ? $path === '/admin' : ($path === $href || str_starts_with($path, $href . '/'));
$flashes = flashes();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? 'Admin') . ' — ' . setting('site_name')) ?></title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="topbar">
  <img src="<?= e(img(setting('logo_light'))) ?>" alt="<?= e(setting('site_name')) ?>" width="100" height="34">
  <button type="button" data-side-toggle aria-label="Open menu" aria-controls="side" aria-expanded="false"><?= icon('menu') ?></button>
</div>
<div class="shell">
  <aside class="side" id="side">
    <a class="logo" href="<?= e(url('/admin')) ?>"><img src="<?= e(img(setting('logo_light'))) ?>" alt="<?= e(setting('site_name')) ?> admin" width="124" height="42"></a>
    <nav aria-label="Admin">
      <?php foreach ($nav as [$group, $links]):
          $links = array_filter($links, fn($l) => auth_can($l[3]));
          if (!$links) continue; ?>
        <?php if ($group): ?><div class="group"><?= e($group) ?></div><?php endif; ?>
        <?php foreach ($links as $l): ?>
          <a href="<?= e(url($l[0])) ?>"<?= $isCurrent($l[0]) ? ' aria-current="page"' : '' ?>><?= icon($l[2]) ?><?= e($l[1]) ?><?php if (!empty($l[4])): ?><span class="count"><?= (int) $l[4] ?></span><?php endif; ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="foot">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('ext') ?>View website</a>
      <div class="me">
        <span class="av"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
        <a href="<?= e(url('/admin/profile')) ?>" style="padding:0;display:block;flex:1;min-width:0"><strong><?= e($u['name']) ?></strong><small><?= e(ROLES[$u['role']] ?? $u['role']) ?></small></a>
        <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button class="icon-btn" style="background:transparent;border-color:rgba(255,255,255,.15);color:#B9C1EC" aria-label="Sign out" title="Sign out"><?= icon('logout') ?></button></form>
      </div>
    </div>
  </aside>
  <main class="main" id="main">
    <?php foreach ($flashes as $f): ?>
      <div class="alert <?= e($f['type']) ?>" role="status"><?= icon($f['type'] === 'error' ? 'alert' : 'checkcircle') ?><span><?= e($f['message']) ?></span></div>
    <?php endforeach; ?>
    <?php if (setting('maintenance') === '1'): ?>
      <div class="alert warn"><?= icon('alert') ?><span>Maintenance mode is on — visitors see a “back soon” page. <a href="<?= e(url('/admin/settings#system')) ?>">Turn it off</a></span></div>
    <?php endif; ?>
    <?= $content ?>
  </main>
</div>

<div class="modal" id="mediaModal" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle">
  <div class="modal-box">
    <div class="modal-head"><h3 id="mediaModalTitle">Choose an image</h3><button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <label class="dropzone" data-dropzone>
        <input type="file" accept="image/*" multiple hidden data-upload-input>
        <strong>Drop images here or click to upload</strong><br><span class="small">JPG, PNG or WebP up to 12 MB. Large photos are resized automatically.</span>
      </label>
      <div class="media-grid" data-media-grid></div>
      <div style="text-align:center;margin-top:14px"><button type="button" class="btn btn-ghost btn-sm" data-media-more hidden>Load more</button></div>
    </div>
    <div class="modal-foot"><span class="muted small" data-media-hint>Click an image to select it.</span><div style="display:flex;gap:8px"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button type="button" class="btn btn-dark" data-media-use disabled>Use image</button></div></div>
  </div>
</div>
<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<script>window.ADMIN = <?= json_encode(['base' => base_path(), 'csrf' => csrf_token()], JSON_HEX_TAG) ?>;</script>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
