<?php /** @var array $v @var array $others */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head<?= $v['cover_image'] ? ' has-img' : '' ?>">
  <?php if ($v['cover_image']): ?><img src="<?= e(img_w($v['cover_image'], 2000)) ?>" alt="" width="2000" height="1200" fetchpriority="high"><?php endif; ?>
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('/visas')) ?>">Visas</a></li></ol>
    <h1><?= e($v['country']) ?> visa</h1>
    <p class="lead"><?= e($v['visa_type']) ?></p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap pkg-layout">
    <div>
      <div class="facts">
        <div><small>Processing</small><b><?= e($v['processing_time'] ?: '—') ?></b></div>
        <div><small>Validity</small><b><?= e($v['validity'] ?: '—') ?></b></div>
        <div><small>Stay</small><b><?= e($v['stay'] ?: '—') ?></b></div>
        <div><small>Entries</small><b><?= e($v['entry'] ?: '—') ?></b></div>
      </div>
      <?php if ($v['summary']): ?><div class="pkg-section"><p class="lead" style="color:var(--ink)"><?= e($v['summary']) ?></p></div><?php endif; ?>
      <?php if ($v['requirements']): ?>
      <div class="pkg-section">
        <h2>Documents you will need</h2>
        <ul class="inc-list yes"><?php foreach ($v['requirements'] as $r): ?><li><?= icon('check') ?><span><?= e($r) ?></span></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <?php if ($v['description']): ?><div class="pkg-section"><h2>Good to know</h2><div class="prose"><?= $v['description'] ?></div></div><?php endif; ?>
      <?php if ($others): ?>
      <div class="pkg-section">
        <h2>Other visas</h2>
        <div class="visa-cards" style="display:grid"><?php foreach ($others as $o): ?><a href="<?= e(url('/visas/' . $o['slug'])) ?>"><strong><?= e($o['country']) ?></strong><small><?= e($o['visa_type']) ?></small><b><?= $o['price'] ? e(price($o['price'])) : '—' ?></b></a><?php endforeach; ?></div>
      </div>
      <?php endif; ?>
    </div>
    <aside id="apply">
      <div class="book-card">
        <div class="book-price"><b><?= $v['price'] ? e(price($v['price'])) : 'On request' ?></b></div>
        <div class="book-note">per applicant, for most nationalities</div>
        <?php if ($v['price']): ?><?php if (is_converted()): ?><p class="price-note"><?= icon('info') ?><span>Approximate price in <?= e(visitor_currency()) ?>, converted from <?= e(money($v['price'])) ?>. Your consultant confirms the final amount.</span></p><?php endif; ?><?php endif; ?>
        <?= partial('enquiry_form', ['type' => 'visa', 'subject' => $v['country'] . ' — ' . $v['visa_type'], 'submit' => 'Start my application', 'showPax' => false, 'extra' => [
            ['name' => 'details[Nationality]', 'label' => 'Nationality', 'placeholder' => 'e.g. Pakistani'],
            ['name' => 'adults', 'label' => 'Applicants', 'type' => 'number', 'value' => '1'],
        ]]) ?>
        <div class="or">or</div>
        <a class="btn btn-wa btn-block" href="<?= e(wa_link('Hello! I need a ' . $v['country'] . ' ' . mb_strtolower((string) $v['visa_type']) . '. What documents do I need?')) ?>" target="_blank" rel="noopener" data-wa="visa" data-wa-subject="<?= e($v['country']) ?>"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
      </div>
    </aside>
  </div>
</section>
