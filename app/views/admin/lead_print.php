<?php
/** @var array $lead @var ?array $package @var string $kind */
$isInvoice = $kind === 'invoice';
$docNo = ($isInvoice ? 'INV-' : 'Q-') . $lead['ref'];
$rows = lead_summary_rows($lead, $package);
unset($rows['Reference'], $rows['Message']);
$total = $lead['quoted_amount'] !== null ? (float) $lead['quoted_amount'] : ($package ? (float) $package['price'] * max(1, (int) $lead['adults'] + (int) $lead['children']) : null);
$paid = (float) $lead['paid_amount'];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($docNo) ?> — <?= e($lead['name']) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head><body style="background:#EEF1FA;padding:24px 12px">
<div class="no-print" style="max-width:820px;margin:0 auto 14px;display:flex;gap:8px;justify-content:flex-end">
  <a class="btn btn-ghost" href="<?= e(url('/admin/leads/' . $lead['id'])) ?>">Back to lead</a>
  <button class="btn btn-dark" onclick="window.print()"><?= icon('printer') ?>Print or save as PDF</button>
</div>
<div class="print-doc">
  <header>
    <div><img src="<?= e(img(setting('logo'))) ?>" alt="<?= e(setting('site_name')) ?>" width="190" height="64"></div>
    <div style="text-align:right">
      <h1 style="margin:0"><?= $isInvoice ? 'Invoice' : 'Quotation' ?></h1>
      <div class="muted"><?= e($docNo) ?></div>
      <div class="muted">Date: <?= e(date('j M Y')) ?></div>
      <?php if (!$isInvoice): ?><div class="muted">Valid until: <?= e(date('j M Y', strtotime('+7 days'))) ?></div><?php endif; ?>
    </div>
  </header>
  <div class="grid g2" style="margin-bottom:10px">
    <div><strong>From</strong><br><?= e(setting('legal_name')) ?><br><span class="muted"><?= e(setting('address')) ?><br><?= e(setting('phone')) ?> · <?= e(setting('email')) ?><?= setting('license_no') ? '<br>Licence ' . e(setting('license_no')) : '' ?></span></div>
    <div><strong><?= $isInvoice ? 'Bill to' : 'Prepared for' ?></strong><br><?= e($lead['name'] ?: '—') ?><br><span class="muted"><?= e($lead['phone']) ?><?= $lead['email'] ? '<br>' . e($lead['email']) : '' ?></span></div>
  </div>
  <table>
    <?php foreach ($rows as $k => $v): if ($v === null || $v === '') continue; ?><tr><th><?= e($k) ?></th><td><?= nl2br(e((string) $v)) ?></td></tr><?php endforeach; ?>
    <?php if ($package && ($inc = json_dec($package['inclusions'], []))): ?><tr><th>Included</th><td><?= implode('<br>', array_map(fn($x) => '• ' . e($x), $inc)) ?></td></tr><?php endif; ?>
    <?php if ($package && ($exc = json_dec($package['exclusions'], []))): ?><tr><th>Not included</th><td><?= implode('<br>', array_map(fn($x) => '• ' . e($x), $exc)) ?></td></tr><?php endif; ?>
  </table>
  <table>
    <tr><th>Total</th><td class="total"><?= $total !== null ? e(money($total, null, true)) : 'To be confirmed' ?></td></tr>
    <?php if ($isInvoice): ?>
      <tr><th>Paid</th><td><?= e(money($paid, null, true)) ?></td></tr>
      <tr><th>Balance due</th><td class="total"><?= $total !== null ? e(money(max(0, $total - $paid), null, true)) : '—' ?></td></tr>
    <?php endif; ?>
  </table>
  <p class="small muted"><?= $isInvoice ? 'Please quote ' . e($docNo) . ' with your payment.' : 'Prices are subject to availability until the booking is confirmed with a deposit. Airline and hotel cancellation rules apply.' ?></p>
  <?php if ($lead['agent_name']): ?><p class="small">Your consultant: <strong><?= e($lead['agent_name']) ?></strong><?= $lead['agent_email'] ? ' · ' . e($lead['agent_email']) : '' ?></p><?php endif; ?>
  <footer><?= e(setting('legal_name')) ?> · <?= e(setting('address')) ?> · <?= e(setting('phone')) ?> · <?= e(abs_url('/')) ?></footer>
</div>
</body></html>
