<?php /** @var array $page */ $GLOBALS['_title'] = 'Activity log'; ?>
<div class="page-top"><div><h1>Activity log</h1><p>Who changed what, and when. Useful when several people manage the website.</p></div></div>
<div class="table-wrap">
  <table class="t">
    <thead><tr><th>When</th><th>Who</th><th>What</th><th class="hide-sm">IP address</th></tr></thead>
    <tbody>
    <?php foreach ($page['items'] as $a): ?>
      <tr>
        <td class="nowrap muted" title="<?= e($a['created_at']) ?>"><?= e(time_ago($a['created_at'])) ?></td>
        <td><?= e($a['user_name'] ?: 'System') ?></td>
        <td><?= e($a['summary'] ?: $a['action']) ?></td>
        <td class="hide-sm muted"><?= e($a['ip']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$page['items']): ?><tr><td colspan="4" class="empty-row">Nothing logged yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pager($page) ?>
