<?php
/** @var array $lead @var ?array $package @var array $notes @var array $staff @var array $history */
$GLOBALS['_title'] = 'Lead ' . $lead['ref'];
$details = json_dec($lead['details'] ?? '', []);
$utm = json_dec($lead['utm'] ?? '', []);
$first = explode(' ', (string) $lead['name'])[0] ?: 'there';
$waPhone = preg_replace('/\D/', '', (string) $lead['phone']);
$waText = 'Hello ' . $first . ', this is ' . auth_user()['name'] . ' from ' . setting('site_name') . ' about your request ' . $lead['ref'] . '.';
$balance = $lead['quoted_amount'] !== null ? (float) $lead['quoted_amount'] - (float) $lead['paid_amount'] : null;
?>
<a class="crumb" href="<?= e(url('/admin/leads')) ?>"><?= icon('left') ?>All leads</a>
<div class="page-top">
  <div class="lead-head">
    <h1><?= e($lead['name'] ?: 'Website visitor') ?></h1>
    <span class="lead-ref"><?= e($lead['ref']) ?></span>
    <?= lead_badge($lead['status']) ?>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('/admin/leads/' . $lead['id'] . '/print')) ?>" target="_blank"><?= icon('printer') ?>Quotation</a>
    <a class="btn btn-ghost" href="<?= e(url('/admin/leads/' . $lead['id'] . '/print', ['kind' => 'invoice'])) ?>" target="_blank"><?= icon('file') ?>Invoice</a>
    <?php if (auth_can('editor')): ?>
    <form method="post" action="<?= e(url('/admin/leads/' . $lead['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" type="submit" data-confirm="Delete this lead and its notes? This cannot be undone."><?= icon('trash') ?>Delete</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="grid g-main">
  <div class="grid">
    <div class="card">
      <div class="card-head"><h2>Request</h2><span class="muted small">Received <?= e(fmt_date($lead['created_at'], 'D j M Y, H:i')) ?> via <?= $lead['channel'] === 'whatsapp' ? 'WhatsApp button' : 'website form' ?></span></div>
      <dl class="kv">
        <dt>Type</dt><dd><?= e(lead_type_label($lead['type'])) ?></dd>
        <dt>Subject</dt><dd><?= e($lead['subject'] ?: '—') ?><?php if ($package): ?> · <a href="<?= e(url('/packages/' . $package['slug'])) ?>" target="_blank">view package</a> (from <?= e(money($package['price'])) ?> pp)<?php endif; ?></dd>
        <?php if ($lead['travel_date']): ?><dt>Travel date</dt><dd><?= e(fmt_date($lead['travel_date'], 'D j M Y')) ?><?= $lead['return_date'] ? ' – ' . e(fmt_date($lead['return_date'], 'D j M Y')) : '' ?></dd><?php endif; ?>
        <dt>Travellers</dt><dd><?= (int) $lead['adults'] ?> adult(s)<?= $lead['children'] ? ', ' . (int) $lead['children'] . ' child(ren)' : '' ?><?= $lead['infants'] ? ', ' . (int) $lead['infants'] . ' infant(s)' : '' ?></dd>
        <?php if ($lead['budget']): ?><dt>Budget</dt><dd><?= e($lead['budget']) ?></dd><?php endif; ?>
        <?php foreach ($details as $k => $v): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endforeach; ?>
        <?php if ($lead['message']): ?><dt>Message</dt><dd style="white-space:pre-wrap;font-weight:500"><?= e($lead['message']) ?></dd><?php endif; ?>
        <?php if ($lead['source_page']): ?><dt>Sent from</dt><dd class="muted"><?= e($lead['source_page']) ?></dd><?php endif; ?>
        <?php if ($utm): ?><dt>Campaign</dt><dd class="muted"><?= e(implode(' · ', array_map(fn($k, $v) => "$k: $v", array_keys($utm), $utm))) ?></dd><?php endif; ?>
      </dl>
    </div>

    <div class="card" id="notes">
      <div class="card-head"><h2>Notes &amp; history</h2></div>
      <form method="post" action="<?= e(url('/admin/leads/' . $lead['id'] . '/note')) ?>" style="margin-bottom:18px">
        <?= csrf_field() ?>
        <label class="sr-only" for="noteBody">Add a note</label>
        <textarea class="inp" id="noteBody" name="body" rows="3" placeholder="Call summary, quote sent, customer preferences…" required></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:8px"><button class="btn btn-dark btn-sm" type="submit">Add note</button></div>
      </form>
      <ol class="timeline">
        <?php foreach ($notes as $n): ?>
          <li class="<?= e($n['kind']) ?>">
            <span class="dot"><?= icon($n['kind'] === 'note' ? 'edit' : ($n['kind'] === 'status' ? 'refresh' : 'info')) ?></span>
            <div><div class="body"><?= $n['kind'] === 'status' ? 'Status changed: <strong>' . e($n['body']) . '</strong>' : e($n['body']) ?></div><small><?= e($n['author'] ?: 'System') ?> · <?= e(fmt_date($n['created_at'], 'j M Y, H:i')) ?></small></div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="card-head"><h2>Contact</h2></div>
      <dl class="kv" style="grid-template-columns:70px 1fr">
        <dt>Phone</dt><dd><?= $lead['phone'] ? '<a href="' . e(tel_link($lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '—' ?></dd>
        <dt>Email</dt><dd><?= $lead['email'] ? '<a href="mailto:' . e($lead['email']) . '">' . e($lead['email']) . '</a>' : '—' ?></dd>
      </dl>
      <div class="quick-contact" style="margin-top:14px">
        <?php if (strlen($waPhone) >= 8): ?><a class="btn btn-wa btn-sm" href="https://wa.me/<?= e($waPhone) ?>?text=<?= e(rawurlencode($waText)) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>WhatsApp</a><?php endif; ?>
        <?php if ($lead['phone']): ?><a class="btn btn-ghost btn-sm" href="<?= e(tel_link($lead['phone'])) ?>"><?= icon('phone') ?>Call</a><?php endif; ?>
        <?php if ($lead['email']): ?><a class="btn btn-ghost btn-sm" href="mailto:<?= e($lead['email']) ?>?subject=<?= e(rawurlencode('Your request ' . $lead['ref'])) ?>"><?= icon('mail') ?>Email</a><?php endif; ?>
      </div>
    </div>

    <form class="card" method="post" action="<?= e(url('/admin/leads/' . $lead['id'])) ?>" data-dirty>
      <?= csrf_field() ?>
      <div class="card-head"><h2>Manage</h2></div>
      <div class="fld" style="margin-bottom:14px">
        <span class="label">Status</span>
        <div class="pipeline" data-pipeline>
          <?php foreach (LEAD_STATUSES as $k => $s): ?><button type="button" data-value="<?= e($k) ?>" aria-pressed="<?= $lead['status'] === $k ? 'true' : 'false' ?>"><?= e($s['label']) ?></button><?php endforeach; ?>
        </div>
        <input type="hidden" name="status" value="<?= e($lead['status']) ?>">
        <span class="help">Customers see the status on the Track page.</span>
      </div>
      <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <div class="fld c2"><label for="assigned">Assigned to</label><select class="inp" id="assigned" name="assigned_to"><option value="0">Nobody</option><?php foreach ($staff as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $lead['assigned_to'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="fld"><label for="quoted">Quoted (<?= e(setting('currency')) ?>)</label><input class="inp" id="quoted" name="quoted_amount" inputmode="decimal" value="<?= e(amount_input($lead['quoted_amount'])) ?>"></div>
        <div class="fld"><label for="paid">Paid (<?= e(setting('currency')) ?>)</label><input class="inp" id="paid" name="paid_amount" inputmode="decimal" value="<?= e(amount_input($lead['paid_amount'])) ?>"></div>
        <?php if ($balance !== null): ?><div class="fld c2"><span class="help">Balance due: <strong><?= e(money($balance, null, true)) ?></strong></span></div><?php endif; ?>
        <div class="fld"><label for="td">Travel date</label><input class="inp" id="td" type="date" name="travel_date" value="<?= e($lead['travel_date']) ?>"></div>
        <div class="fld"><label for="rd">Return date</label><input class="inp" id="rd" type="date" name="return_date" value="<?= e($lead['return_date']) ?>"></div>
        <div class="fld c2"><label for="fu">Follow up on</label><input class="inp" id="fu" type="datetime-local" name="follow_up_at" value="<?= $lead['follow_up_at'] ? e(date('Y-m-d\TH:i', strtotime($lead['follow_up_at']))) : '' ?>"></div>
        <details class="fld c2"><summary class="label" style="cursor:pointer">Edit customer details</summary>
          <div class="form-grid" style="grid-template-columns:1fr 1fr;margin-top:10px">
            <div class="fld c2"><label for="ln">Name</label><input class="inp" id="ln" name="name" value="<?= e($lead['name']) ?>"></div>
            <div class="fld"><label for="lp">Phone</label><input class="inp" id="lp" name="phone" value="<?= e($lead['phone']) ?>"></div>
            <div class="fld"><label for="le">Email</label><input class="inp" id="le" name="email" type="email" value="<?= e($lead['email']) ?>"></div>
            <div class="fld c2"><label for="ls">Subject</label><input class="inp" id="ls" name="subject" value="<?= e($lead['subject']) ?>"></div>
            <div class="fld"><label for="la">Adults</label><input class="inp" id="la" name="adults" type="number" min="0" value="<?= (int) $lead['adults'] ?>"></div>
            <div class="fld"><label for="lc">Children</label><input class="inp" id="lc" name="children" type="number" min="0" value="<?= (int) $lead['children'] ?>"></div>
          </div>
        </details>
      </div>
      <button class="btn btn-dark btn-block" type="submit" style="margin-top:16px"><?= icon('save') ?>Save changes</button>
    </form>

    <?php if ($history): ?>
    <div class="card">
      <div class="card-head"><h2>Other requests from this customer</h2></div>
      <ul class="checklist">
        <?php foreach ($history as $h): ?><li><a href="<?= e(url('/admin/leads/' . $h['id'])) ?>"><?= lead_badge($h['status']) ?><span><?= e(str_limit($h['subject'] ?: $h['ref'], 34)) ?><br><small class="muted"><?= e(fmt_date($h['created_at'])) ?></small></span></a></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>
