<?php
/** @var array $res @var array $page @var string $qs @var array $filters */
$GLOBALS['_title'] = $res['label'];
$name = $res['name'];
$canSort = !empty($res['sortable']) && $qs === '' && !array_filter(array_column($filters, 'value'));
$query = array_filter(array_merge(['q' => $qs], array_map(fn($f) => $f['value'], $filters)));
?>
<div class="page-top">
  <div><h1><?= e($res['label']) ?></h1><p><?= plural($page['total'], $res['singular']) ?><?= $canSort ? ' · drag rows to change the order on the website' : '' ?></p></div>
  <div class="actions"><a class="btn" href="<?= e(url('/admin/' . $name . '/new')) ?>"><?= icon('plus') ?>New <?= e($res['singular']) ?></a></div>
</div>

<form class="toolbar" method="get">
  <label class="search"><?= icon('search') ?><span class="sr-only">Search</span><input name="q" value="<?= e($qs) ?>" placeholder="Search <?= e(mb_strtolower($res['label'])) ?>"></label>
  <?php foreach ($filters as $col => $f): ?>
    <select class="select" name="<?= e($col) ?>" onchange="this.form.submit()" aria-label="<?= e($f['label']) ?>">
      <option value="">All <?= e(mb_strtolower($f['label'])) ?>s</option>
      <?php foreach ($f['options'] as $o): ?><option<?= $f['value'] === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
    </select>
  <?php endforeach; ?>
  <button class="btn btn-ghost btn-sm" type="submit">Search</button>
  <?php if ($query): ?><a class="link-btn small" href="<?= e(url('/admin/' . $name)) ?>">Clear</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="t" data-resource="<?= e($name) ?>"<?= $canSort ? ' data-sortable data-offset="' . (($page['page'] - 1) * $page['perPage']) . '"' : '' ?>>
    <thead><tr>
      <?php if ($canSort): ?><th style="width:28px"><span class="sr-only">Order</span></th><?php endif; ?>
      <?php foreach ($res['list'] as $col => $c): ?><th<?= in_array($c['type'], ['money'], true) ? ' class="num"' : '' ?>><?= e($c['label'] ?? '') ?></th><?php endforeach; ?>
      <th class="num"><span class="sr-only">Actions</span></th>
    </tr></thead>
    <tbody>
    <?php foreach ($page['items'] as $item): $edit = url('/admin/' . $name . '/' . $item['id']); ?>
      <tr data-id="<?= (int) $item['id'] ?>"<?= $canSort ? ' draggable="true"' : '' ?>>
        <?php if ($canSort): ?><td class="drag" title="Drag to reorder"><?= icon('drag') ?></td><?php endif; ?>
        <?php foreach ($res['list'] as $col => $c): $v = $item[$col] ?? null; ?>
          <td<?= $c['type'] === 'money' ? ' class="num"' : '' ?>>
            <?php switch ($c['type']):
                case 'image': ?><img class="thumb<?= !empty($c['round']) ? ' round' : '' ?>" src="<?= e($v ? img_w($v, 160) : url('/assets/img/placeholder.svg')) ?>" alt="" loading="lazy" width="64" height="46"><?php break;
                case 'title': ?><a class="title" href="<?= e($edit) ?>"><?= e(str_limit((string) $v, 80)) ?></a><?php if (!empty($c['sub'])): ?><span class="sub"><?= e(fill_template($c['sub'], $item)) ?></span><?php endif; ?><?php break;
                case 'money': ?><?= $v !== null && $v !== '' ? e(money($v)) : '<span class="muted">—</span>' ?><?php break;
                case 'toggle': ?><label class="switch toggle-cell"><input type="checkbox" data-toggle="<?= e($col) ?>"<?= (int) $v ? ' checked' : '' ?> aria-label="<?= e(($c['label'] ?? $col) . ': ' . ($item[$res['title']] ?? '')) ?>"><i></i></label><?php break;
                case 'badge': ?><span class="badge <?= $v === 'published' ? 'green' : 'plain' ?>"><?= e(ucfirst((string) $v)) ?></span><?php break;
                case 'date': ?><span class="muted nowrap"><?= $v ? e(fmt_date($v)) : '—' ?></span><?php break;
                case 'stars': ?><span style="color:var(--orange);white-space:nowrap"><?= str_repeat('★', (int) $v) ?></span><?php break;
                default: ?><?= e((string) ($v ?? '—')) ?>
            <?php endswitch; ?>
          </td>
        <?php endforeach; ?>
        <td>
          <div class="actions">
            <?php if (!empty($res['public']) && isset($item['slug'])): ?><a class="icon-btn" href="<?= e(url(str_replace('{slug}', $item['slug'], $res['public']))) ?>" target="_blank" rel="noopener" title="View on website" aria-label="View on website"><?= icon('eye') ?></a><?php endif; ?>
            <a class="icon-btn" href="<?= e($edit) ?>" title="Edit" aria-label="Edit"><?= icon('edit') ?></a>
            <form method="post" action="<?= e(url('/admin/' . $name . '/' . $item['id'] . '/duplicate')) ?>"><?= csrf_field() ?><button class="icon-btn" title="Duplicate" aria-label="Duplicate"><?= icon('copy') ?></button></form>
            <form method="post" action="<?= e(url('/admin/' . $name . '/' . $item['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="icon-btn danger" title="Delete" aria-label="Delete" data-confirm="Delete “<?= e(str_limit((string) ($item[$res['title']] ?? ''), 60)) ?>”? This cannot be undone."><?= icon('trash') ?></button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$page['items']): ?><tr><td colspan="<?= count($res['list']) + 2 ?>" class="empty-row"><?= $query ? 'Nothing matches your search.' : 'Nothing here yet.' ?> <a href="<?= e(url('/admin/' . $name . '/new')) ?>">Add the first <?= e($res['singular']) ?></a></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pager($page, $query) ?>
