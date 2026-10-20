<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(setting('site_name')) ?> — back soon</title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#000C64;color:#fff;font:17px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:24px;text-align:center}main{max-width:560px}img{height:70px;width:auto;margin:0 auto 32px}h1{font-size:clamp(2rem,6vw,3.2rem);line-height:1.05;margin:0 0 16px}p{color:#C9D0F5}a{display:inline-block;margin:12px 6px 0;padding:14px 22px;border-radius:999px;background:#25D366;color:#05361B;font-weight:700;text-decoration:none}a.alt{background:#FF6600;color:#000C64}</style></head>
<body><main>
<img src="<?= e(img(setting('logo_light'))) ?>" alt="<?= e(setting('site_name')) ?>">
<h1>We will be back shortly.</h1>
<p><?= e(setting('maintenance_message')) ?></p>
<?php if (wa_number()): ?><a href="<?= e(wa_link('Hello! I would like to make a booking.')) ?>">WhatsApp us</a><?php endif; ?>
<?php if (setting('phone')): ?><a class="alt" href="<?= e(tel_link(setting('phone'))) ?>">Call <?= e(setting('phone')) ?></a><?php endif; ?>
</main></body></html>
