<?php /** @var array $users @var array $u */ $GLOBALS['_title'] = 'Team'; ?>
<div class="page-top">
  <div><h1>Team</h1><p>Give each person their own login. Sales agents only see leads; content editors also manage the website.</p></div>
  <div class="actions"><a class="btn" href="<?= e(url('/admin/users/new')) ?>"><?= icon('plus') ?>Add team member</a></div>
</div>
<div class="table-wrap">
  <table class="t">
    <thead><tr><th>Name</th><th>Role</th><th class="hide-sm">Last sign-in</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $x): ?>
      <tr>
        <td><a class="title" href="<?= e(url('/admin/users/' . $x['id'])) ?>"><?= e($x['name']) ?></a><?= (int) $x['id'] === (int) $u['id'] ? ' <span class="badge plain">You</span>' : '' ?><span class="sub"><?= e($x['email']) ?></span></td>
        <td><?= e(ROLES[$x['role']] ?? $x['role']) ?></td>
        <td class="hide-sm muted"><?= $x['last_login_at'] ? e(time_ago($x['last_login_at'])) : 'Never' ?></td>
        <td><?= (int) $x['active'] ? '<span class="badge green">Active</span>' : '<span class="badge">Disabled</span>' ?></td>
        <td><div class="actions">
          <a class="icon-btn" href="<?= e(url('/admin/users/' . $x['id'])) ?>" aria-label="Edit"><?= icon('edit') ?></a>
          <?php if ((int) $x['id'] !== (int) $u['id']): ?><form method="post" action="<?= e(url('/admin/users/' . $x['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="icon-btn danger" aria-label="Remove" data-confirm="Remove <?= e($x['name']) ?>? Their leads will become unassigned."><?= icon('trash') ?></button></form><?php endif; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
