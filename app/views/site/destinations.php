<?php /** @var array $list */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Destinations</li></ol>
    <h1>Where to next?</h1>
    <p class="lead">Each guide covers the best time to go, the trips we run there and how to get there from home.</p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap">
    <?php if ($list): ?>
    <div class="dest-cards">
      <?php foreach ($list as $d): ?>
        <a class="dest-card" href="<?= e(url('/destinations/' . $d['slug'])) ?>">
          <img src="<?= e(img_w($d['cover_image'], 800)) ?>" alt="" loading="lazy" width="800" height="1066">
          <?php if ($d['airport_code']): ?><span class="code"><?= e($d['airport_code']) ?></span><?php endif; ?>
          <div class="in">
            <h3><?= e($d['name']) ?></h3>
            <p><?= e($d['tagline']) ?></p>
            <p style="margin-top:8px;color:#fff;font-weight:700"><?= $d['trips'] ? plural((int) $d['trips'], 'package') . ' from ' . e(price($d['from_price'])) : 'Tailor-made trips' ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty"><h3>Destination guides are on their way</h3><p>Meanwhile, tell us where you want to go and we will plan it.</p><a class="btn" href="<?= e(url('/contact')) ?>">Plan my trip</a></div>
    <?php endif; ?>
  </div>
</section>
<?= partial('cta') ?>
