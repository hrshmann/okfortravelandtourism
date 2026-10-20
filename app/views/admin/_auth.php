<?php /** Shared shell for login / forgot / reset. @var string $inner @var string $pageTitle */ ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?> — <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head><body class="auth-body">
<div class="auth">
  <aside class="auth-art" aria-hidden="true">
    <img class="auth-logo" src="<?= e(img(setting('logo_light'))) ?>" alt="" width="220" height="75">
    <svg class="auth-path" viewBox="0 0 800 400" preserveAspectRatio="xMaxYMid meet">
      <path d="M-40 380 C 220 360, 470 290, 640 150 S 780 40, 840 10" fill="none" stroke="#FF6600" stroke-width="3" stroke-linecap="round"/>
      <path d="M-40 400 C 240 382, 495 310, 668 172 S 805 62, 860 34" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="1.5" stroke-linecap="round"/>
      <path transform="translate(744 70) rotate(-32) scale(1.6)" fill="#fff" d="M30 0c0-3-4-4-8-4H6L-8-22h-6l8 18h-14l-6-7h-4l3 11-3 11h4l6-7h14l-8 18h6L6 4h16c4 0 8-1 8-4z"/>
    </svg>
    <div class="auth-copy">
      <h2>Every journey is OK.</h2>
      <p>Answer leads, update packages and keep the website fresh — from any device.</p>
    </div>
  </aside>
  <main class="auth-form">
    <a class="auth-back" href="<?= e(url('/')) ?>"><?= icon('left') ?>Back to website</a>
    <div class="auth-card">
      <img class="auth-logo-sm" src="<?= e(img(setting('logo'))) ?>" alt="<?= e(setting('site_name')) ?>" width="170" height="58">
      <?= $inner ?>
    </div>
    <p class="auth-foot">© <?= date('Y') ?> <?= e(setting('legal_name')) ?></p>
  </main>
</div>
<script>
// Show / hide password
document.querySelectorAll('[data-reveal]').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = document.getElementById(b.dataset.reveal), show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(show));
    b.textContent = show ? 'Hide' : 'Show';
  });
});
</script>
</body></html>
