<?php
/** @var string $content */
$s = $seo ?? seo();
// Templates with a dark top section set $GLOBALS['_layout_dark'] so the header starts transparent.
$darkHero = $darkHero ?? !empty($GLOBALS['_layout_dark']);
$bodyClass = trim(($darkHero ? 'dark-hero ' : '') . ($bodyClass ?? $GLOBALS['_body_class'] ?? ''));
$siteName = (string) setting('site_name');
$logo = img(setting('logo'));
$logoLight = img(setting('logo_light'));
$nav = [
    '/packages' => 'Holidays',
    '/umrah' => 'Umrah',
    '/flights' => 'Flights',
    '/visas' => 'Visas',
    '/destinations' => 'Destinations',
    '/blog' => 'Journal',
    '/contact' => 'Contact',
];
$ga = trim((string) setting('ga4_id', ''));
$pixel = trim((string) setting('meta_pixel_id', ''));
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
$flashes = flashes();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($s['title']) ?></title>
<meta name="description" content="<?= e($s['description']) ?>">
<link rel="canonical" href="<?= e($s['canonical']) ?>">
<?php if ($s['noindex']): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
<meta property="og:type" content="<?= e($s['type']) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($s['title']) ?>">
<meta property="og:description" content="<?= e($s['description']) ?>">
<meta property="og:url" content="<?= e($s['canonical']) ?>">
<meta property="og:image" content="<?= e($s['image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#000C64">
<?php if ($v = setting('gsc_verification', '')): ?><meta name="google-site-verification" content="<?= e($v) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
<link rel="icon" type="image/png" href="<?= e(url('/assets/img/icon-192.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('/assets/img/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
<?php foreach ($s['schema'] as $schema): ?>
<script type="application/ld+json"><?= json_encode($schema, $jsonFlags) ?></script>
<?php endforeach; ?>
<?php if ($ga !== '' && preg_match('/^G-[A-Z0-9]+$/i', $ga)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
<?php endif; ?>
<?php if ($pixel !== '' && ctype_digit($pixel)): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($pixel) ?>');fbq('track','PageView');</script>
<?php endif; ?>
<?= (string) setting('head_code', '') ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip" href="#main">Skip to content</a>

<?php if ($a = trim((string) setting('announcement', ''))): ?>
<div class="announce"><?= e($a) ?></div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
  <div class="wrap">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> home">
      <img class="logo-dark" src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" width="200" height="68">
      <img class="logo-light" src="<?= e($logoLight) ?>" alt="" width="200" height="68">
    </a>
    <nav aria-label="Main">
      <ul class="nav">
        <?php foreach ($nav as $href => $label): ?>
          <li><a href="<?= e(url($href)) ?>"<?= is_active($href) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-cta">
      <?= partial('currency_switch') ?>
      <a class="header-phone" href="<?= e(tel_link(setting('phone'))) ?>"><?= icon('phone') ?><?= e(setting('phone')) ?></a>
      <a class="btn btn-dark btn-sm" href="<?= e(url('/contact', ['type' => 'holiday'])) ?>">Plan my trip</a>
      <button class="menu-btn" id="menuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu"><?= icon('menu') ?></button>
    </div>
  </div>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <div class="top">
    <img src="<?= e($logoLight) ?>" alt="<?= e($siteName) ?>" width="160" height="55">
    <button class="icon-btn" id="menuClose" aria-label="Close menu" style="background:transparent;color:#fff;border-color:rgba(255,255,255,.3)"><?= icon('x') ?></button>
  </div>
  <nav aria-label="Mobile">
    <a href="<?= e(url('/')) ?>">Home<?= icon('arrow') ?></a>
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= e(url($href)) ?>"><?= e($label) ?><?= icon('arrow') ?></a>
    <?php endforeach; ?>
    <a href="<?= e(url('/track')) ?>">Track a request<?= icon('arrow') ?></a>
  </nav>
  <div class="contact">
    <?= partial('currency_switch', ['chips' => true]) ?>
    <a class="btn btn-wa btn-block" href="<?= e(wa_link('Hello! I would like help planning a trip.')) ?>" data-wa="contact" target="_blank" rel="noopener"><?= icon('whatsapp') ?>WhatsApp us</a>
    <a class="btn btn-ghost btn-block" href="<?= e(tel_link(setting('phone'))) ?>"><?= icon('phone') ?>Call <?= e(setting('phone')) ?></a>
  </div>
</div>

<?php foreach ($flashes as $f): ?>
  <div class="flash-bar <?= e($f['type']) ?>" role="status"><div class="wrap"><?= e($f['message']) ?></div></div>
<?php endforeach; ?>

<main id="main">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="foot-top">
      <div>
        <img src="<?= e($logoLight) ?>" alt="<?= e($siteName) ?>" width="170" height="58" loading="lazy">
        <p style="max-width:34ch">Flights, holidays, Umrah and visas — planned by people you can reach on WhatsApp.</p>
        <?php if ($social = social_links()): ?>
        <div class="socials">
          <?php foreach ($social as $k => $sl): ?>
            <a href="<?= e($sl['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($sl['label']) ?>"><?= icon($k === 'x' ? 'xbrand' : $k) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <h4>Travel</h4>
        <ul>
          <li><a href="<?= e(url('/packages')) ?>">Holiday packages</a></li>
          <li><a href="<?= e(url('/umrah')) ?>">Umrah &amp; Hajj</a></li>
          <li><a href="<?= e(url('/flights')) ?>">Flights</a></li>
          <li><a href="<?= e(url('/visas')) ?>">Visa services</a></li>
          <li><a href="<?= e(url('/destinations')) ?>">Destinations</a></li>
        </ul>
      </div>
      <div>
        <h4>Company</h4>
        <ul>
          <li><a href="<?= e(url('/about')) ?>">About us</a></li>
          <li><a href="<?= e(url('/blog')) ?>">Travel journal</a></li>
          <li><a href="<?= e(url('/faq')) ?>">FAQ</a></li>
          <li><a href="<?= e(url('/track')) ?>">Track a request</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Get in touch</h4>
        <ul class="foot-contact">
          <li><?= icon('phone') ?><a href="<?= e(tel_link(setting('phone'))) ?>"><?= e(setting('phone')) ?></a></li>
          <li><?= icon('mail') ?><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
          <li><?= icon('pin') ?><span><?= e(setting('address')) ?></span></li>
          <li><?= icon('clock') ?><span><?= e(setting('office_hours')) ?></span></li>
        </ul>
        <form class="newsletter" action="<?= e(url('/subscribe')) ?>" method="post" data-ajax="subscribe">
          <?= csrf_field() ?><?= form_guard() ?>
          <label class="sr-only" for="nlEmail">Email for offers</label>
          <input id="nlEmail" type="email" name="email" placeholder="Email for offers" required autocomplete="email">
          <button class="btn btn-sm" type="submit">Subscribe</button>
        </form>
      </div>
    </div>
    <p class="foot-big" aria-hidden="true">Every journey is OK.</p>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e(setting('legal_name')) ?><?= ($lic = setting('license_no', '')) ? ' · Licence ' . e($lic) : '' ?></span>
      <nav aria-label="Legal">
        <?php foreach (footer_pages() as $fp): ?>
          <a href="<?= e(url('/' . $fp['slug'])) ?>"><?= e($fp['title']) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</footer>

<?php if (wa_number() !== ''): ?>
<div class="wa-panel" id="waPanel" role="dialog" aria-label="Chat on WhatsApp" aria-hidden="true">
  <header>
    <img src="<?= e(url('/assets/img/icon-192.png')) ?>" alt="" width="44" height="44">
    <div><strong><?= e($siteName) ?></strong><small>Usually replies within minutes</small></div>
    <button id="waClose" aria-label="Close"><?= icon('x') ?></button>
  </header>
  <div class="body">
    <div class="wa-bubble">Hello! What can we help you plan? Pick a topic and WhatsApp opens with your message ready.</div>
    <div class="wa-topics">
      <?php foreach ([
          ['holiday', 'A holiday package', 'Hello! I would like help with a holiday package.'],
          ['umrah', 'Umrah or Hajj', 'Assalamu alaikum! I would like details about Umrah packages.'],
          ['flight', 'A flight ticket', 'Hello! I need a flight ticket.'],
          ['visa', 'A visa', 'Hello! I need help with a visa.'],
      ] as [$t, $label, $msg]): ?>
        <a href="<?= e(wa_link($msg)) ?>" target="_blank" rel="noopener" data-wa="<?= e($t) ?>" data-wa-subject="<?= e($label) ?>"><?= e($label) ?><?= icon('arrow') ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<button class="wa-fab" id="waFab" aria-label="Chat on WhatsApp" aria-expanded="false" aria-controls="waPanel"><?= icon('whatsapp') ?></button>
<?php endif; ?>

<div class="toast-wrap" id="toasts" aria-live="polite"></div>
<script>window.OK = <?= json_encode(array_merge([
    'base' => base_path(),
    'wa' => wa_number(),
    'site' => $siteName,
], ['fx' => currency_js()]), $jsonFlags) ?>;</script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
