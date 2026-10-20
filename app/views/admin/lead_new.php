<?php /** @var array $packages */ $GLOBALS['_title'] = 'Add lead'; ?>
<a class="crumb" href="<?= e(url('/admin/leads')) ?>"><?= icon('left') ?>All leads</a>
<div class="page-top"><div><h1>Add a lead</h1><p>For enquiries that came in by phone, walk-in or social media. It is assigned to you.</p></div></div>
<form class="card" method="post" action="<?= e(url('/admin/leads/new')) ?>" style="max-width:860px">
  <?= csrf_field() ?>
  <div class="form-grid">
    <div class="fld c2"><label for="n">Customer name</label><input class="inp" id="n" name="name" required></div>
    <div class="fld c1"><label for="p">Phone / WhatsApp</label><input class="inp" id="p" name="phone" type="tel" placeholder="+971…"></div>
    <div class="fld c1"><label for="e">Email</label><input class="inp" id="e" name="email" type="email"></div>
    <div class="fld c1"><label for="t">Type</label><select class="inp" id="t" name="type"><?php foreach (LEAD_TYPES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="fld c3"><label for="pk">Package (optional)</label><select class="inp" id="pk" name="package_id"><option value="">—</option><?php foreach ($packages as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
    <div class="fld c4"><label for="s">Subject</label><input class="inp" id="s" name="subject" placeholder="e.g. Family holiday to Baku in December"></div>
    <div class="fld c1"><label for="d">Travel date</label><input class="inp" id="d" name="travel_date" type="date"></div>
    <div class="fld c1"><label for="a">Adults</label><input class="inp" id="a" name="adults" type="number" min="1" value="2"></div>
    <div class="fld c1"><label for="c">Children</label><input class="inp" id="c" name="children" type="number" min="0" value="0"></div>
    <div class="fld c1"><label for="b">Budget</label><input class="inp" id="b" name="budget"></div>
    <div class="fld c4"><label for="m">Notes</label><textarea class="inp" id="m" name="message" rows="4"></textarea></div>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="btn btn-dark" type="submit"><?= icon('save') ?>Save lead</button></div>
</form>
