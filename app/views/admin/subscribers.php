<?php /** @var array $page @var string $qs */ $GLOBALS['_title'] = 'Subscribers'; ?>
<div class="page-top">
  <div><h1>Subscribers</h1><p><?= plural($page['total'], 'person', 'people') ?> signed up for offers. Export the list to Mailchimp, Brevo or your WhatsApp broadcast tool.</p></div>
  <div class="actions"><a class="btn btn-ghost" href="<?= e(url('/admin/subscribers/export')) ?>"><?= icon('download') ?>Export CSV</a></div>
</div>
<form class="toolbar" method="get"><label class="search"><?= icon('search') ?><input name="q" value="<?= e($qs) ?>" placeholder="Search email or name" aria-label="Search"></label></form>
<div class="table-wrap">
  <table class="t">
    <thead><tr><th>Email</th><th>Name</th><th>Signed up from</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($page['items'] as $s): ?>
      <tr>
        <td><a class="title" href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></td>
        <td><?= e($s['name'] ?: '—') ?></td>
        <td class="muted"><?= e($s['source'] ?: '—') ?></td>
        <td class="muted nowrap"><?= e(fmt_date($s['created_at'])) ?></td>
        <td><div class="actions"><form method="post" action="<?= e(url('/admin/subscribers/' . $s['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="icon-btn danger" aria-label="Remove subscriber" data-confirm="Remove <?= e($s['email']) ?> from the list?"><?= icon('trash') ?></button></form></div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$page['items']): ?><tr><td colspan="5" class="empty-row">No subscribers yet. Visitors sign up with the form in the website footer.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pager($page, ['q' => $qs ?: null]) ?>
