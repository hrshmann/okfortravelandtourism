<?php /** @var array $p */ $w = !empty($big) ? 1200 : 800; ?>
<a class="pkg" href="<?= e($p['url']) ?>">
  <div class="pkg-media">
    <img src="<?= e(img_w($p['cover_image'], $w)) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="800" height="600">
    <div class="pkg-tags">
      <?php if ($p['badge']): ?><span class="tag"><?= e($p['badge']) ?></span><?php else: ?><span></span><?php endif; ?>
      <?php if ($p['save']): ?><span class="tag tag-orange">Save <?= (int) $p['save'] ?>%</span><?php endif; ?>
    </div>
    <div class="pkg-price">
      <small>From</small>
      <b><?= e(price($p['price'])) ?></b><?php if ($p['save']): ?><s><?= e(price($p['old_price'])) ?></s><?php endif; ?>
    </div>
  </div>
  <div class="pkg-body">
    <h3><?= e($p['title']) ?></h3>
    <div class="pkg-meta">
      <span><?= icon('pin') ?><?= e($p['location']) ?></span>
      <span><?= icon('calendar') ?><?= (int) $p['days'] ?> days, <?= (int) $p['nights'] ?> nights</span>
    </div>
    <?php if (!empty($withSummary) && $p['summary']): ?><p><?= e($p['summary']) ?></p><?php endif; ?>
  </div>
</a>
