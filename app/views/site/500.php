<?php
// Rendered without the layout or database, so it must stand on its own.
$phone = '';
$wa = '';
try {
    $phone = (string) setting('phone', '');
    $wa = wa_number();
} catch (Throwable $e) {
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Something went wrong</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#000C64;color:#fff;font:17px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:24px}main{max-width:560px}h1{font-size:clamp(2rem,6vw,3.4rem);line-height:1;margin:0 0 16px;letter-spacing:-.02em}p{color:#C9D0F5}a{display:inline-block;margin:10px 10px 0 0;padding:14px 22px;border-radius:999px;background:#FF6600;color:#000C64;font-weight:700;text-decoration:none}a.alt{background:#25D366;color:#05361B}</style></head>
<body><main>
<h1>We hit some turbulence.</h1>
<p>Something went wrong on our side and the team has been notified. Please try again in a minute<?= $phone ? ', or contact us directly.' : '.' ?></p>
<a href="javascript:location.reload()">Try again</a>
<?php if ($wa): ?><a class="alt" href="https://wa.me/<?= htmlspecialchars($wa) ?>">WhatsApp us</a><?php endif; ?>
</main></body></html>
