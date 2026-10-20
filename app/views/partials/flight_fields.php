<?php
/** Flight search fields. Wrapped by a <form data-flight-form> on the homepage and the Flights page. */
$pf = $prefill ?? [];
$from = $pf['from'] ?? airport('DXB');
$to = $pf['to'] ?? null;
$depart = (string) input('date', date('Y-m-d', strtotime('+14 days')));
$ret = (string) input('return', input('date') ? '' : date('Y-m-d', strtotime('+21 days')));
$oneway = input('date') !== '' && input('return') === '';
$adults = max(1, min(9, input_int('adults', 1)));
$children = max(0, min(8, input_int('children', 0)));
$cabin = array_key_exists((string) input('cabin'), CABINS) ? (string) input('cabin') : 'economy';
$apLabel = fn($a) => $a ? $a['city'] . ' (' . $a['code'] . ')' : '';
?>
<div class="trip-type" role="radiogroup" aria-label="Trip type">
  <label class="chip-radio"><input type="radio" name="trip" value="return"<?= $oneway ? '' : ' checked' ?>><span>Return</span></label>
  <label class="chip-radio"><input type="radio" name="trip" value="oneway"<?= $oneway ? ' checked' : '' ?>><span>One way</span></label>
</div>
<div class="search-row" style="--cols:5">
  <div class="field" data-ac="from">
    <label for="fromInput">From</label>
    <input id="fromInput" value="<?= e($apLabel($from)) ?>" placeholder="City or airport" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="fromList" aria-autocomplete="list">
    <input type="hidden" name="from" value="<?= e($from['code'] ?? '') ?>">
    <span class="sub"><?= e($from ? $from['name'] : 'Where from?') ?></span>
    <div class="ac-list" id="fromList" role="listbox"></div>
    <button type="button" class="swap" data-swap aria-label="Swap airports"><?= icon('swap') ?></button>
  </div>
  <div class="field" data-ac="to">
    <label for="toInput">To</label>
    <input id="toInput" value="<?= e($apLabel($to)) ?>" placeholder="City or airport" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="toList" aria-autocomplete="list">
    <input type="hidden" name="to" value="<?= e($to['code'] ?? '') ?>">
    <span class="sub"><?= e($to ? $to['name'] : 'Where to?') ?></span>
    <div class="ac-list" id="toList" role="listbox"></div>
  </div>
  <div class="field"><label for="departDate">Depart</label><input id="departDate" name="date" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($depart) ?>" required></div>
  <div class="field" data-return<?= $oneway ? ' style="opacity:.45"' : '' ?>><label for="returnDate">Return</label><input id="returnDate" name="return" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($oneway ? '' : $ret) ?>"<?= $oneway ? ' disabled' : '' ?>></div>
  <div class="field" data-pax-field>
    <span class="label" id="paxLabel">Travellers &amp; cabin</span>
    <button type="button" class="pax-btn" aria-haspopup="dialog" aria-expanded="false" aria-describedby="paxLabel" style="text-align:left;font-weight:650;padding:2px 0 0"><span data-pax-text></span></button>
    <span class="sub" data-cabin-text><?= e(CABINS[$cabin]) ?></span>
    <input type="hidden" name="adults" value="<?= $adults ?>"><input type="hidden" name="children" value="<?= $children ?>">
    <div class="pop" role="dialog" aria-label="Travellers and cabin">
      <div class="counter-row"><div><strong>Adults</strong><small>12 years and over</small></div><div class="counter" data-counter="adults" data-min="1" data-max="9"><button type="button" data-step="-1" aria-label="Fewer adults">−</button><output><?= $adults ?></output><button type="button" data-step="1" aria-label="More adults">+</button></div></div>
      <div class="counter-row"><div><strong>Children</strong><small>2 to 11 years</small></div><div class="counter" data-counter="children" data-min="0" data-max="8"><button type="button" data-step="-1" aria-label="Fewer children">−</button><output><?= $children ?></output><button type="button" data-step="1" aria-label="More children">+</button></div></div>
      <div class="cabins">
        <?php foreach (CABINS as $k => $label): ?><label><input type="radio" name="cabin" value="<?= e($k) ?>"<?= $k === $cabin ? ' checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-dark btn-sm btn-block" data-pax-done style="margin-top:14px">Done</button>
    </div>
  </div>
  <button class="btn" type="submit"><?= icon('search') ?>Search flights</button>
</div>
<input type="hidden" name="auto" value="1">
