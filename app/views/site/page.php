<?php /** @var array $page */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><?= e($page['title']) ?></li></ol>
    <h1><?= e($page['title']) ?></h1>
    <?php if ($page['updated_at']): ?><p class="lead">Last updated <?= e(fmt_date($page['updated_at'])) ?></p><?php endif; ?>
  </div>
</section>
<section class="section-tight">
  <div class="wrap wrap-narrow"><div class="prose"><?= $page['body'] ?></div></div>
</section>
