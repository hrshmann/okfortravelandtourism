<?php
/** @var array $page @var array $f @var array $categories @var array $destinations @var array $durations @var array $budgets @var array $sorts @var string $title */
$GLOBALS['_layout_dark'] = true;
$q = array_filter($f, fn($v, $k) => $v !== '' && !($k === 'sort' && $v === 'recommended'), ARRAY_FILTER_USE_BOTH);
$with = fn(array $over) => url('/packages', array_merge($q, $over, ['page' => null]));
$destNames = array_column($destinations, 'name', 'slug');
$chips = [];
if ($f['q'] !== '') $chips[] = ['“' . $f['q'] . '”', $with(['q' => null])];
if ($f['category'] !== '') $chips[] = [$f['category'], $with(['category' => null])];
if ($f['destination'] !== '') $chips[] = [$destNames[$f['destination']] ?? $f['destination'], $with(['destination' => null])];
if (isset($durations[$f['duration']])) $chips[] = [$durations[$f['duration']][2], $with(['duration' => null])];
if (isset($budgets[$f['budget']])) $chips[] = [$budgets[$f['budget']][2], $with(['budget' => null])];
?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Holidays</li></ol>
    <h1><?= e($title) ?></h1>
    <p class="lead">Flights, hotels, transfers and tours in one price. Every trip can be adjusted — dates, hotels, nights and activities.</p>
  </div>
</section>

<section class="section-tight">
  <div class="wrap listing">
    <aside>
      <button class="btn btn-ghost btn-sm filters-toggle" type="button" data-filters-toggle aria-expanded="false" aria-controls="filters" style="margin-bottom:16px"><?= icon('filter') ?>Filters</button>
      <div class="filters" id="filters">
        <form action="<?= e(url('/packages')) ?>" method="get" role="search">
          <?php foreach (array_diff_key($q, ['q' => 1]) as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
          <label class="sr-only" for="pq">Search packages</label>
          <div class="searchbox"><?= icon('search') ?><input id="pq" name="q" value="<?= e($f['q']) ?>" placeholder="Search trips"><button class="btn btn-sm" type="submit">Go</button></div>
        </form>
        <div>
          <h3>Trip type</h3>
          <ul class="filter-list">
            <li><a href="<?= e($with(['category' => null])) ?>"<?= $f['category'] === '' ? ' aria-current="true"' : '' ?>>All trips</a></li>
            <?php foreach ($categories as $c => $n): ?><li><a href="<?= e($with(['category' => $c])) ?>"<?= $f['category'] === $c ? ' aria-current="true"' : '' ?>><?= e($c) ?><small><?= (int) $n ?></small></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php if ($destinations): ?>
        <div>
          <h3>Destination</h3>
          <ul class="filter-list">
            <li><a href="<?= e($with(['destination' => null])) ?>"<?= $f['destination'] === '' ? ' aria-current="true"' : '' ?>>Anywhere</a></li>
            <?php foreach ($destinations as $d): ?><li><a href="<?= e($with(['destination' => $d['slug']])) ?>"<?= $f['destination'] === $d['slug'] ? ' aria-current="true"' : '' ?>><?= e($d['name']) ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <div>
          <h3>Length</h3>
          <ul class="filter-list">
            <li><a href="<?= e($with(['duration' => null])) ?>"<?= $f['duration'] === '' ? ' aria-current="true"' : '' ?>>Any length</a></li>
            <?php foreach ($durations as $k => $d): ?><li><a href="<?= e($with(['duration' => $k])) ?>"<?= $f['duration'] === $k ? ' aria-current="true"' : '' ?>><?= e($d[2]) ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <div>
          <h3>Budget per person</h3>
          <ul class="filter-list">
            <li><a href="<?= e($with(['budget' => null])) ?>"<?= $f['budget'] === '' ? ' aria-current="true"' : '' ?>>Any budget</a></li>
            <?php foreach ($budgets as $k => $b): ?><li><a href="<?= e($with(['budget' => $k])) ?>"<?= $f['budget'] === $k ? ' aria-current="true"' : '' ?>><?= e($b[2]) ?></a></li><?php endforeach; ?>
          </ul>
        </div>
      </div>
    </aside>

    <div>
      <div class="listing-bar">
        <strong><?= plural($page['total'], 'trip') ?></strong>
        <form action="<?= e(url('/packages')) ?>" method="get">
          <?php foreach (array_diff_key($q, ['sort' => 1]) as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
          <label class="sr-only" for="psort">Sort by</label>
          <select class="select-sm" id="psort" name="sort" data-autosubmit>
            <?php foreach ($sorts as $k => $s): ?><option value="<?= e($k) ?>"<?= $f['sort'] === $k ? ' selected' : '' ?>><?= e($s[1]) ?></option><?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-sm" type="submit">Sort</button></noscript>
        </form>
      </div>
      <?php if ($chips): ?>
        <div class="active-filters"><?php foreach ($chips as [$label, $href]): ?><a href="<?= e($href) ?>" aria-label="Remove filter <?= e($label) ?>"><?= e($label) ?><?= icon('x') ?></a><?php endforeach; ?><a href="<?= e(url('/packages')) ?>" style="background:none">Clear all</a></div>
      <?php endif; ?>

      <?php if ($page['items']): ?>
        <div class="pkg-grid"><?php foreach ($page['items'] as $p): ?><?= partial('pkg_card', ['p' => $p, 'withSummary' => true]) ?><?php endforeach; ?></div>
        <?= pager($page, $q) ?>
      <?php else: ?>
        <div class="empty">
          <h3>No trips match those filters</h3>
          <p>We build custom trips every day. Tell us where and when, and a consultant will put together options for you.</p>
          <div class="hero-actions" style="justify-content:center">
            <a class="btn" href="<?= e(url('/contact', ['type' => 'holiday'])) ?>">Request a custom trip</a>
            <a class="btn btn-ghost" href="<?= e(url('/packages')) ?>">Clear filters</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?= partial('cta', ['ctaTitle' => 'Not seeing the right trip?', 'ctaText' => 'Every package here started as someone’s request. Tell us yours and we will build it — flights, hotels, visas and all.']) ?>
