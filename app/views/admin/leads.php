<?php
/** @var array $f @var array $page @var array $statusCounts @var array $staff */
$GLOBALS['_title'] = 'Leads';
$base = array_filter($f, fn($v, $k) => $v !== '' && !($k === 'channel' && $v === 'form'), ARRAY_FILTER_USE_BOTH);
$link = fn(array $over) => url('/admin/leads', array_merge($base, $over, ['page' => null]));
$isWa = $f['channel'] === 'whatsapp';
?>
<div class="page-top">
  <div><h1>Leads &amp; bookings</h1><p>Every request from the website, with its status, value and history.</p></div>
  <div class="actions">
    <?php if (auth_can('editor')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/leads/export', $base)) ?>"><?= icon('download') ?>Export CSV</a><?php endif; ?>
    <a class="btn" href="<?= e(url('/admin/leads/new')) ?>"><?= icon('plus') ?>Add lead</a>
  </div>
</div>

<div class="tabs">
  <a href="<?= e(url('/admin/leads')) ?>"<?= !$isWa ? ' aria-current="page"' : '' ?>>Requests</a>
  <a href="<?= e(url('/admin/leads', ['channel' => 'whatsapp'])) ?>"<?= $isWa ? ' aria-current="page"' : '' ?>>WhatsApp clicks</a>
</div>

<?php if ($isWa): ?>
  <div class="alert info"><?= icon('info') ?><span>Each row is a visitor who opened WhatsApp from the website, with the message they were about to send. The conversation itself continues in WhatsApp.</span></div>
<?php endif; ?>

<form class="toolbar" method="get" action="<?= e(url('/admin/leads')) ?>">
  <?php if ($isWa): ?><input type="hidden" name="channel" value="whatsapp"><?php endif; ?>
  <label class="search"><?= icon('search') ?><span class="sr-only">Search</span><input name="q" value="<?= e($f['q']) ?>" placeholder="Name, phone, email or reference"></label>
  <select class="select" name="type" onchange="this.form.submit()" aria-label="Type"><option value="">All types</option><?php foreach (LEAD_TYPES as $k => $l): ?><option value="<?= e($k) ?>"<?= $f['type'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  <?php if (!$isWa): ?>
  <select class="select" name="agent" onchange="this.form.submit()" aria-label="Assigned to"><option value="">Everyone’s leads</option><option value="me"<?= $f['agent'] === 'me' ? ' selected' : '' ?>>Assigned to me</option><option value="none"<?= $f['agent'] === 'none' ? ' selected' : '' ?>>Unassigned</option></select>
  <?php endif; ?>
  <input type="hidden" name="status" value="<?= e($f['status']) ?>">
  <button class="btn btn-ghost btn-sm" type="submit">Search</button>
  <?php if ($f['q'] !== '' || $f['type'] !== '' || $f['agent'] !== '' || $f['status'] !== ''): ?><a class="link-btn small" href="<?= e(url('/admin/leads', $isWa ? ['channel' => 'whatsapp'] : [])) ?>">Clear</a><?php endif; ?>
</form>

<?php if (!$isWa): ?>
<div class="seg" style="margin-bottom:14px">
  <a href="<?= e($link(['status' => null])) ?>"<?= $f['status'] === '' ? ' aria-current="true"' : '' ?>>All <small><?= array_sum($statusCounts) ?></small></a>
  <a href="<?= e($link(['status' => 'open'])) ?>"<?= $f['status'] === 'open' ? ' aria-current="true"' : '' ?>>Open <small><?= ($statusCounts['new'] ?? 0) + ($statusCounts['contacted'] ?? 0) + ($statusCounts['quoted'] ?? 0) ?></small></a>
  <?php foreach (LEAD_STATUSES as $k => $s): ?><a href="<?= e($link(['status' => $k])) ?>"<?= $f['status'] === $k ? ' aria-current="true"' : '' ?>><?= e($s['label']) ?> <small><?= (int) ($statusCounts[$k] ?? 0) ?></small></a><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/leads/bulk')) ?>" data-bulk>
  <?= csrf_field() ?>
  <div class="table-wrap">
    <table class="t">
      <thead><tr>
        <?php if (auth_can('editor')): ?><th style="width:34px"><input type="checkbox" data-check-all aria-label="Select all"></th><?php endif; ?>
        <th>Customer</th><th>Request</th><?php if (!$isWa): ?><th>Status</th><th class="hide-sm">Travel</th><th class="hide-sm num">Value</th><th class="hide-sm">Agent</th><?php else: ?><th>Message</th><?php endif; ?><th class="num">Received</th>
      </tr></thead>
      <tbody>
      <?php foreach ($page['items'] as $l): ?>
        <tr class="<?= $l['status'] === 'new' && !$isWa ? 'unread' : '' ?>">
          <?php if (auth_can('editor')): ?><td><input type="checkbox" name="ids[]" value="<?= (int) $l['id'] ?>" aria-label="Select <?= e($l['ref']) ?>"></td><?php endif; ?>
          <td><a class="title" href="<?= e(url('/admin/leads/' . $l['id'])) ?>"><?= e($l['name'] ?: ($isWa ? 'WhatsApp visitor' : 'No name')) ?></a><span class="sub"><?= e($l['phone'] ?: $l['email'] ?: $l['ref']) ?></span></td>
          <td><?= e(str_limit($l['subject'] ?: lead_type_label($l['type']), 46)) ?><span class="sub"><?= e(lead_type_label($l['type'])) ?> · <?= e($l['ref']) ?></span></td>
          <?php if (!$isWa): ?>
            <td><?= lead_badge($l['status']) ?></td>
            <td class="hide-sm nowrap"><?= $l['travel_date'] ? e(fmt_date($l['travel_date'], 'j M Y')) : '<span class="muted">—</span>' ?><span class="sub"><?= (int) $l['adults'] + (int) $l['children'] + (int) $l['infants'] ?> pax</span></td>
            <td class="hide-sm num"><?= $l['quoted_amount'] !== null ? e(money($l['quoted_amount'])) : '<span class="muted">—</span>' ?><?php if ($l['paid_amount']): ?><span class="sub">paid <?= e(money($l['paid_amount'])) ?></span><?php endif; ?></td>
            <td class="hide-sm"><?= e($l['agent_name'] ?: '—') ?></td>
          <?php else: ?>
            <td class="muted small"><?= e(str_limit($l['message'], 90)) ?></td>
          <?php endif; ?>
          <td class="num muted" title="<?= e($l['created_at']) ?>"><?= e(time_ago($l['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$page['items']): ?><tr><td colspan="8" class="empty-row"><?= $f['q'] !== '' || $f['status'] !== '' || $f['type'] !== '' ? 'No leads match these filters.' : ($isWa ? 'No WhatsApp clicks yet.' : 'No requests yet. They appear here as soon as someone uses a form on the website.') ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (auth_can('editor') && $page['items']): ?>
  <div class="toolbar" style="margin-top:12px" data-bulk-bar hidden>
    <span class="muted small" data-bulk-count></span>
    <select class="select" name="action" aria-label="Bulk action">
      <?php if (!$isWa): foreach (LEAD_STATUSES as $k => $s): ?><option value="<?= e($k) ?>">Mark as <?= e(mb_strtolower($s['label'])) ?></option><?php endforeach; endif; ?>
      <option value="delete">Delete</option>
    </select>
    <button class="btn btn-dark btn-sm" type="submit" data-confirm="Apply this action to the selected leads?">Apply</button>
  </div>
  <?php endif; ?>
</form>
<?= pager($page, $base) ?>
