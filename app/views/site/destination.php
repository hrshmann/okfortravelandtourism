<?php /** @var array $d @var array $packages */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head has-img">
  <img src="<?= e(img_w($d['cover_image'], 2000)) ?>" alt="" width="2000" height="1200" fetchpriority="high">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('/destinations')) ?>">Destinations</a></li></ol>
    <h1><?= e($d['name']) ?></h1>
    <p class="lead"><?= e($d['tagline']) ?></p>
    <div class="head-meta">
      <?php if ($d['country'] && $d['country'] !== $d['name']): ?><span class="tag"><?= icon('globe') ?><?= e($d['country']) ?></span><?php endif; ?>
      <?php if ($d['best_time']): ?><span class="tag"><?= icon('calendar') ?>Best time: <?= e($d['best_time']) ?></span><?php endif; ?>
      <?php if ($d['airport_code']): ?><span class="tag"><?= icon('plane') ?>Fly into <?= e($d['airport_code']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="wrap pkg-layout">
    <div>
      <div class="prose"><?= $d['description'] ?></div>
    </div>
    <aside>
      <div class="book-card">
        <h2 class="h-s" style="margin-bottom:.3em">Plan a trip to <?= e($d['name']) ?></h2>
        <p class="muted small">Tell us your dates and we will send options with flights, hotels and transfers.</p>
        <?= partial('enquiry_form', ['type' => 'holiday', 'subject' => 'Trip to ' . $d['name'], 'submit' => 'Get a quote']) ?>
        <?php if ($d['airport_code']): ?>
          <div class="or">or</div>
          <a class="btn btn-ghost btn-block" href="<?= e(url('/flights', ['to' => $d['airport_code']])) ?>"><?= icon('plane') ?>Search flights to <?= e($d['airport_code']) ?></a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>

<?php if ($packages): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-m"><?= e($d['name']) ?> packages</h2><p><?= plural(count($packages), 'trip') ?> ready to book, all adjustable.</p></div></div>
    <div class="pkg-grid"><?php foreach ($packages as $p): ?><?= partial('pkg_card', ['p' => $p, 'withSummary' => true]) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?= partial('cta') ?>
