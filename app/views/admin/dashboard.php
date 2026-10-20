<?php
/** @var array $u @var array $kpi @var array $series @var array $funnel @var array $latest @var array $followups @var array $topPackages @var array $topPages @var array $checklist */
$GLOBALS['_title'] = 'Dashboard';
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$delta = $kpi['lastMonth'] ? (int) round(($kpi['month'] - $kpi['lastMonth']) / $kpi['lastMonth'] * 100) : null;
$doneCount = count(array_filter($checklist, fn($c) => $c['done']));

// Chart geometry: visits as navy bars, leads as orange bars (own scale, shown in the legend).
$W = 720; $H = 200; $pad = 24; $n = count($series);
$maxV = max(1, max(array_column($series, 'visits')));
$maxL = max(1, max(array_column($series, 'leads')));
$bw = ($W - $pad) / $n;
?>
<div class="page-top">
  <div><h1><?= e($greet) ?>, <?= e(explode(' ', $u['name'])[0]) ?></h1><p>Here is what is happening with your website.</p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('/admin/leads/new')) ?>"><?= icon('plus') ?>Add lead</a>
    <?php if (auth_can('editor')): ?><a class="btn" href="<?= e(url('/admin/packages/new')) ?>"><?= icon('plus') ?>New package</a><?php endif; ?>
  </div>
</div>

<?php if ($doneCount < count($checklist) && auth_can('admin')): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-head"><h2>Get ready to launch</h2><span class="muted small"><?= $doneCount ?> of <?= count($checklist) ?> done</span></div>
  <div class="progress"><span style="width:<?= (int) round($doneCount / count($checklist) * 100) ?>%"></span></div>
  <ul class="checklist" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
    <?php foreach ($checklist as $c): ?><li class="<?= $c['done'] ? 'done' : '' ?>"><a href="<?= e(url($c['url'])) ?>"><span class="tick"><?= $c['done'] ? icon('check') : '' ?></span><?= e($c['label']) ?></a></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid g4" style="margin-bottom:16px">
  <a class="kpi" href="<?= e(url('/admin/leads', ['status' => 'new'])) ?>"><small><?= icon('inbox') ?>New requests</small><b><?= $kpi['new'] ?></b><span class="delta">waiting for a reply</span></a>
  <div class="kpi"><small><?= icon('chart') ?>Requests this month</small><b><?= $kpi['month'] ?></b><span class="delta <?= $delta === null ? '' : ($delta >= 0 ? 'up' : 'down') ?>"><?= $delta === null ? 'first month of data' : ($delta >= 0 ? '+' : '') . $delta . '% vs last month' ?></span></div>
  <div class="kpi"><small><?= icon('checkcircle') ?>Confirmed this month</small><b><?= $kpi['confirmed'] ?></b><span class="delta"><?= e(money($kpi['revenue'])) ?> received</span></div>
  <div class="kpi"><small><?= icon('tag') ?>Open quotes</small><b><?= e(money($kpi['pipeline'])) ?></b><span class="delta">quoted, not yet confirmed</span></div>
</div>

<div class="grid g-main">
  <div class="grid">
    <div class="card">
      <div class="card-head"><h2>Last 30 days</h2>
        <div class="legend"><span><i style="background:var(--navy)"></i><?= number_format($kpi['visits']) ?> page views</span><span><i style="background:var(--orange)"></i><?= (int) array_sum(array_column($series, 'leads')) ?> requests &amp; WhatsApp clicks</span></div></div>
      <svg class="chart" viewBox="0 0 <?= $W ?> <?= $H + 22 ?>" role="img" aria-label="Page views and requests per day for the last 30 days">
        <?php for ($g = 0; $g <= 4; $g++): $y = $H - $H * $g / 4; ?><line class="grid-line" x1="<?= $pad ?>" x2="<?= $W ?>" y1="<?= $y ?>" y2="<?= $y ?>"/><?php endfor; ?>
        <text x="0" y="10"><?= $maxV ?></text>
        <?php $i = 0; foreach ($series as $day => $s):
            $x = $pad + $i * $bw;
            $hv = $H * $s['visits'] / $maxV; $hl = $H * 0.6 * $s['leads'] / $maxL; ?>
          <rect class="bar" x="<?= round($x + 2, 1) ?>" y="<?= round($H - $hv, 1) ?>" width="<?= round($bw * .55, 1) ?>" height="<?= round($hv, 1) ?>" rx="2"><title><?= e(fmt_date($day, 'D j M')) ?>: <?= $s['visits'] ?> views, <?= $s['leads'] ?> requests</title></rect>
          <?php if ($s['leads']): ?><rect class="bar2" x="<?= round($x + 2 + $bw * .55, 1) ?>" y="<?= round($H - $hl, 1) ?>" width="<?= round($bw * .3, 1) ?>" height="<?= round($hl, 1) ?>" rx="2"/><?php endif; ?>
          <?php if ($i % 7 === 0 && $n - 1 - $i >= 3 || $i === $n - 1): ?><text x="<?= round($x, 1) ?>" y="<?= $H + 16 ?>"><?= e(fmt_date($day, 'j M')) ?></text><?php endif; ?>
        <?php $i++; endforeach; ?>
      </svg>
    </div>

    <div class="card">
      <div class="card-head"><h2>Latest requests</h2><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/leads')) ?>">All leads</a></div>
      <?php if ($latest): ?>
      <div class="table-wrap" style="border:0">
        <table class="t">
          <thead><tr><th>Customer</th><th class="hide-sm">Request</th><th>Status</th><th class="hide-sm">Received</th></tr></thead>
          <tbody>
          <?php foreach ($latest as $l): ?>
            <tr class="<?= $l['status'] === 'new' ? 'unread' : '' ?>">
              <td><a class="title" href="<?= e(url('/admin/leads/' . $l['id'])) ?>"><?= e($l['name'] ?: 'No name') ?></a><span class="sub"><?= e($l['phone'] ?: $l['email']) ?></span></td>
              <td class="hide-sm"><?= e(str_limit($l['subject'] ?: lead_type_label($l['type']), 40)) ?><span class="sub"><?= e(lead_type_label($l['type'])) ?></span></td>
              <td><?= lead_badge($l['status']) ?></td>
              <td class="hide-sm nowrap muted"><?= e(time_ago($l['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="muted">No requests yet. They will appear here as soon as visitors use a form on the website.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="card-head"><h2>Follow-ups due</h2></div>
      <?php if ($followups): ?>
        <ul class="checklist">
          <?php foreach ($followups as $l): $late = strtotime($l['follow_up_at']) < time(); ?>
            <li><a href="<?= e(url('/admin/leads/' . $l['id'])) ?>"><span class="badge <?= $late ? 'red' : 'amber' ?> plain"><?= e(fmt_date($l['follow_up_at'], 'j M H:i')) ?></span><?= e($l['name'] ?: $l['ref']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?><p class="muted small" style="margin:0">Nothing due. Set a follow-up date on any lead to see it here.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card-head"><h2>Pipeline</h2></div>
      <div class="funnel">
        <?php $fmax = max(1, max($funnel)); foreach ($funnel as $k => $nn): ?>
          <a class="funnel-row" href="<?= e(url('/admin/leads', ['status' => $k])) ?>" style="text-decoration:none;color:inherit"><span><?= e(lead_status_label($k)) ?></span><span class="track"><span class="fill" style="width:<?= round($nn / $fmax * 100) ?>%;<?= $k === 'confirmed' || $k === 'completed' ? 'background:var(--green)' : '' ?>"></span></span><b><?= $nn ?></b></a>
        <?php endforeach; ?>
      </div>
      <p class="muted small" style="margin:14px 0 0"><?= $kpi['wa'] ?> WhatsApp clicks in the last 30 days.</p>
    </div>

    <div class="card">
      <div class="card-head"><h2>Most viewed packages</h2></div>
      <table class="t"><tbody>
        <?php foreach ($topPackages as $p): ?>
          <tr><td style="padding-left:0"><a class="title" href="<?= e(url('/admin/packages/' . $p['id'])) ?>"><?= e($p['title']) ?></a></td><td class="num muted"><?= number_format((int) $p['views']) ?> views</td><td class="num" style="padding-right:0"><?= (int) $p['leads'] ?> leads</td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>

    <?php if ($topPages): ?>
    <div class="card">
      <div class="card-head"><h2>Top pages, 30 days</h2></div>
      <table class="t"><tbody>
        <?php foreach ($topPages as $p): ?><tr><td style="padding-left:0"><a href="<?= e(url($p['path'])) ?>" target="_blank" rel="noopener"><?= e($p['path']) ?></a></td><td class="num" style="padding-right:0"><?= number_format((int) $p['n']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <?php endif; ?>
  </div>
</div>
