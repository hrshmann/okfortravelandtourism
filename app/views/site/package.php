<?php
/** @var array $p @var ?array $dest @var array $related */
$GLOBALS['_layout_dark'] = true;
$GLOBALS['_body_class'] = 'has-mobile-book';
$waMsg = 'Hello! I am interested in the "' . $p['title'] . '" package (' . $p['days'] . ' days, from ' . price($p['price']) . ' per person). Please share availability.';
$photos = array_values(array_filter(array_merge([$p['cover_image']], $p['gallery'])));
?>
<section class="page-head has-img">
  <img src="<?= e(img_w($p['cover_image'], 2000)) ?>" alt="" width="2000" height="1200" fetchpriority="high">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('/packages')) ?>">Holidays</a></li><?php if ($p['category']): ?><li><a href="<?= e(url('/packages', ['category' => $p['category']])) ?>"><?= e($p['category']) ?></a></li><?php endif; ?></ol>
    <?php if (!(int) $p['active']): ?><p class="tag tag-orange" style="margin-bottom:14px">Hidden — only admins can see this page</p><?php endif; ?>
    <h1><?= e($p['title']) ?></h1>
    <div class="head-meta">
      <span class="tag"><?= icon('pin') ?><?= e($p['location']) ?></span>
      <span class="tag"><?= icon('calendar') ?><?= (int) $p['days'] ?> days, <?= (int) $p['nights'] ?> nights</span>
      <?php if ($p['rating']): ?><span class="tag"><?= icon('star') ?><?= e(number_format((float) $p['rating'], 1)) ?> rating</span><?php endif; ?>
      <?php if ($p['badge']): ?><span class="tag"><?= icon('tag') ?><?= e($p['badge']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="wrap pkg-layout">
    <div>
      <div class="facts">
        <div><small>Duration</small><b><?= (int) $p['days'] ?>D / <?= (int) $p['nights'] ?>N</b></div>
        <div><small>Trip type</small><b><?= e($p['category'] ?: 'Holiday') ?></b></div>
        <div><small>From</small><b><?= e(price($p['price'])) ?></b></div>
        <div><small>Best time</small><b><?= e($dest['best_time'] ?? 'All year') ?></b></div>
      </div>

      <?php if ($p['highlights']): ?>
      <div class="pkg-section">
        <h2>Highlights</h2>
        <ul class="highlights"><?php foreach ($p['highlights'] as $h): ?><li><?= icon('sparkle') ?><?= e($h) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>

      <?php if ($p['description'] || $p['summary']): ?>
      <div class="pkg-section">
        <h2>Overview</h2>
        <div class="prose"><?= $p['description'] ?: '<p>' . e($p['summary']) . '</p>' ?></div>
      </div>
      <?php endif; ?>

      <?php if ($p['itinerary']): ?>
      <div class="pkg-section">
        <h2>Day by day</h2>
        <ol class="itinerary">
          <?php foreach ($p['itinerary'] as $i => $d): ?>
            <li><span class="day" aria-hidden="true"><?= $i + 1 ?></span><div><h3><span class="sr-only">Day <?= $i + 1 ?>: </span><?= e($d['title'] ?: 'Day ' . ($i + 1)) ?></h3><?php if ($d['text']): ?><p><?= e($d['text']) ?></p><?php endif; ?></div></li>
          <?php endforeach; ?>
        </ol>
      </div>
      <?php endif; ?>

      <?php if ($p['inclusions'] || $p['exclusions']): ?>
      <div class="pkg-section">
        <h2>What the price covers</h2>
        <div class="inc-grid">
          <?php if ($p['inclusions']): ?><div><h3>Included</h3><ul class="inc-list yes"><?php foreach ($p['inclusions'] as $x): ?><li><?= icon('check') ?><span><?= e($x) ?></span></li><?php endforeach; ?></ul></div><?php endif; ?>
          <?php if ($p['exclusions']): ?><div><h3>Not included</h3><ul class="inc-list no"><?php foreach ($p['exclusions'] as $x): ?><li><?= icon('minus') ?><span><?= e($x) ?></span></li><?php endforeach; ?></ul></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (count($photos) > 1): ?>
      <div class="pkg-section">
        <h2>Photos</h2>
        <div class="gallery" data-count="<?= min(5, count($photos)) ?>">
          <?php foreach (array_slice($photos, 0, 5) as $i => $ph): ?>
            <a href="<?= e(img_w($ph, 1800)) ?>" data-lightbox><img src="<?= e(img_w($ph, $i === 0 ? 1200 : 600)) ?>" alt="<?= e($p['title']) ?> photo <?= $i + 1 ?>" loading="lazy" width="600" height="450"></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <aside id="book">
      <div class="book-card">
        <div class="book-price"><b><?= e(price($p['price'])) ?></b><?php if ($p['save']): ?><s><?= e(price($p['old_price'])) ?></s><span class="save-badge">Save <?= (int) $p['save'] ?>%</span><?php endif; ?></div>
        <div class="book-note"><?= e($p['price_note'] ?: 'per person') ?></div>
        <?php if (is_converted()): ?><p class="price-note"><?= icon('info') ?><span>Approximate price in <?= e(visitor_currency()) ?>, converted from <?= e(money($p['price'])) ?>. Your consultant confirms the final amount.</span></p><?php endif; ?>
        <?= partial('enquiry_form', ['type' => 'package', 'packageId' => $p['id'], 'subject' => $p['title'], 'price' => (float) $p['price'], 'submit' => 'Request this trip', 'placeholder' => 'Preferred hotel, room type, flights from your city…']) ?>
        <div class="or">or</div>
        <a class="btn btn-wa btn-block" href="<?= e(wa_link($waMsg)) ?>" target="_blank" rel="noopener" data-wa="package" data-wa-package="<?= (int) $p['id'] ?>" data-wa-subject="<?= e($p['title']) ?>"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
      </div>
    </aside>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-m">You might also like</h2></div><a class="link" href="<?= e(url('/packages')) ?>">All packages<?= icon('arrow') ?></a></div>
    <div class="pkg-grid"><?php foreach ($related as $r): ?><?= partial('pkg_card', ['p' => $r]) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<div class="mobile-book">
  <div><small>From</small><b><?= e(price($p['price'])) ?></b></div>
  <a class="btn" href="#book">Request this trip</a>
</div>
