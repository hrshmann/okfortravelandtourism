<?php
/**
 * Reusable request form. Posts to /enquire; site.js submits it with fetch and swaps in the success state.
 * @var string $type     lead type (see LEAD_TYPES)
 * @var string $subject  optional subject
 * @var array  $extra    extra fields: [['name' => 'details[Nationality]', 'label' => 'Nationality', 'type' => 'text'|'select', 'options' => []]]
 */
$type = $type ?? 'contact';
$extra = $extra ?? [];
$submit = $submit ?? 'Send request';
$showDates = $showDates ?? true;
$showPax = $showPax ?? true;
$uid = 'f' . substr(md5($type . ($subject ?? '') . random_int(0, 99999)), 0, 6);
?>
<form class="enquiry" action="<?= e(url('/enquire')) ?>" method="post" data-ajax="enquiry" novalidate>
  <?= csrf_field() ?><?= form_guard() ?>
  <input type="hidden" name="type" value="<?= e($type) ?>">
  <?php if (!empty($subject)): ?><input type="hidden" name="subject" value="<?= e($subject) ?>"><?php endif; ?>
  <?php if (!empty($packageId)): ?><input type="hidden" name="package_id" value="<?= (int) $packageId ?>"><?php endif; ?>
  <div class="form-grid">
    <div class="inp-wrap full"><label for="<?= $uid ?>n">Full name</label><input class="inp" id="<?= $uid ?>n" name="name" required autocomplete="name"></div>
    <div class="inp-wrap"><label for="<?= $uid ?>p">Phone / WhatsApp</label><input class="inp" id="<?= $uid ?>p" name="phone" type="tel" required autocomplete="tel" placeholder="+971 50 123 4567"></div>
    <div class="inp-wrap"><label for="<?= $uid ?>e">Email (optional)</label><input class="inp" id="<?= $uid ?>e" name="email" type="email" autocomplete="email"></div>
    <?php foreach ($extra as $x): $xid = $uid . preg_replace('/\W/', '', $x['name']); ?>
      <div class="inp-wrap<?= !empty($x['full']) ? ' full' : '' ?>">
        <label for="<?= $xid ?>"><?= e($x['label']) ?></label>
        <?php if (($x['type'] ?? 'text') === 'select'): ?>
          <select class="inp" id="<?= $xid ?>" name="<?= e($x['name']) ?>">
            <?php foreach ($x['options'] as $o): ?><option<?= ($x['value'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <input class="inp" id="<?= $xid ?>" name="<?= e($x['name']) ?>" type="<?= e($x['type'] ?? 'text') ?>" placeholder="<?= e($x['placeholder'] ?? '') ?>" value="<?= e($x['value'] ?? '') ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($showDates): ?>
      <div class="inp-wrap"><label for="<?= $uid ?>d">Travel date</label><input class="inp" id="<?= $uid ?>d" name="travel_date" type="date" min="<?= date('Y-m-d') ?>"></div>
    <?php endif; ?>
    <?php if ($showPax): ?>
      <div class="inp-wrap"><label for="<?= $uid ?>a">Adults</label><input class="inp" id="<?= $uid ?>a" name="adults" type="number" min="1" max="99" value="2" data-pax></div>
      <div class="inp-wrap"><label for="<?= $uid ?>c">Children (2–11)</label><input class="inp" id="<?= $uid ?>c" name="children" type="number" min="0" max="99" value="0" data-pax-child></div>
    <?php endif; ?>
    <div class="inp-wrap full"><label for="<?= $uid ?>m">Anything we should know? (optional)</label><textarea class="inp" id="<?= $uid ?>m" name="message" rows="3" placeholder="<?= e($placeholder ?? 'Dates, hotel preferences, budget, special requests…') ?>"></textarea></div>
  </div>
  <?php if (!empty($price)): ?>
    <?php $pp = price_value($price); ?>
    <div class="total-line" data-total data-price="<?= e((string) $pp) ?>"><span>Estimated total</span><b><?= e(format_currency($pp * 2, visitor_currency())) ?></b></div>
  <?php endif; ?>
  <button class="btn btn-block" type="submit" style="margin-top:<?= !empty($price) ? '0' : '14px' ?>"><?= e($submit) ?></button>
  <p class="form-note">Free and without obligation. A consultant replies with availability and a final price.</p>
</form>
