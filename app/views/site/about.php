<?php /** @var ?array $page @var array $stats @var array $reviews */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>About us</li></ol>
    <h1>Every journey is OK.</h1>
    <p class="lead">That is the promise in our name: clear prices, a real person on WhatsApp, and someone to call if anything changes while you are away.</p>
  </div>
</section>
<section class="section">
  <div class="wrap contact-grid">
    <div class="prose"><?= $page['body'] ?? '' ?></div>
    <div>
      <?php if ($stats): ?>
        <div class="stats" style="grid-template-columns:1fr 1fr;margin-top:0">
          <?php foreach ($stats as $st): ?><div class="stat"><b><?= e($st['value'] ?? '') ?></b><span><?= e($st['label'] ?? '') ?></span></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="contact-cards" style="margin-top:40px">
        <a class="contact-card wa" href="<?= e(wa_link('Hello!')) ?>" target="_blank" rel="noopener" data-wa="contact"><span class="ic"><?= icon('whatsapp') ?></span><span><small>WhatsApp</small><strong>Message a consultant</strong></span></a>
        <div class="contact-card"><span class="ic"><?= icon('pin') ?></span><span><small>Visit us</small><strong><?= e(setting('address')) ?></strong></span></div>
        <?php if ($lic = setting('license_no', '')): ?><div class="contact-card"><span class="ic"><?= icon('shield') ?></span><span><small>Licensed travel agency</small><strong>Licence <?= e($lic) ?></strong></span></div><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php if ($reviews): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-l">From our travellers</h2></div></div>
    <div class="reviews">
      <?php foreach ($reviews as $r): ?>
        <figure class="review"><?= partial('stars', ['rating' => $r['rating']]) ?><blockquote><?= e($r['body']) ?></blockquote><footer><span class="avatar"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span><figcaption><strong><?= e($r['name']) ?></strong><small><?= e($r['trip']) ?></small></figcaption></footer></figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?= partial('cta') ?>
