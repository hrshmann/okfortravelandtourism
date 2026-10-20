<?php
/** Currency picker. Each option is a plain link (?currency=XXX), so it works without JavaScript. */
$list = enabled_currencies();
if (count($list) < 2) {
    return;
}
$cur = visitor_currency();
$link = fn(string $code) => url(current_path(), array_merge(array_diff_key($_GET, ['page' => 1]), ['currency' => $code]));
if (!empty($chips)): ?>
  <div class="cur-chips" role="group" aria-label="Show prices in">
    <?php foreach ($list as $c): ?><a href="<?= e($link($c['code'])) ?>" rel="nofollow"<?= $c['code'] === $cur ? ' aria-current="true"' : '' ?>><?= e($c['code']) ?></a><?php endforeach; ?>
  </div>
<?php else: ?>
  <details class="cur" data-cur>
    <summary aria-label="Prices shown in <?= e($list[$cur]['name']) ?>. Change currency"><span class="cur-sym"><?= e($list[$cur]['symbol']) ?></span><?= e($cur) ?><?= icon('down') ?></summary>
    <div class="cur-menu">
      <p>Show prices in</p>
      <?php foreach ($list as $c): ?>
        <a href="<?= e($link($c['code'])) ?>" rel="nofollow"<?= $c['code'] === $cur ? ' aria-current="true"' : '' ?>><b><?= e($c['code']) ?></b><span><?= e($c['name']) ?></span><?php if ($c['code'] === $cur): ?><?= icon('check') ?><?php endif; ?></a>
      <?php endforeach; ?>
    </div>
  </details>
<?php endif;
