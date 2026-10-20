<?php /** @var array $u @var array $errors */ $GLOBALS['_title'] = 'Your profile'; $err = fn($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : ''; ?>
<div class="page-top"><div><h1>Your profile</h1><p><?= e(ROLES[$u['role']] ?? '') ?> · last sign-in <?= $u['last_login_at'] ? e(time_ago($u['last_login_at'])) : 'now' ?></p></div></div>
<form class="card" method="post" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="form-grid" style="grid-template-columns:1fr 1fr">
    <div class="fld"><label for="n">Name</label><input class="inp" id="n" name="name" value="<?= e($u['name']) ?>" required><?= $err('name') ?></div>
    <div class="fld"><label for="em">Email</label><input class="inp" id="em" name="email" type="email" value="<?= e($u['email']) ?>" required><?= $err('email') ?></div>
    <div class="form-section"><h3>Change password</h3><p>Changing your password signs you out on other devices.</p></div>
    <div class="fld"><label for="pw">New password</label><input class="inp" id="pw" name="password" type="password" autocomplete="new-password"><?= $err('password') ?: '<span class="help">Leave empty to keep your current password.</span>' ?></div>
    <div class="fld"><label for="cp">Current password</label><input class="inp" id="cp" name="current_password" type="password" autocomplete="current-password"><?= $err('current_password') ?: '<span class="help">Needed to change your email or password.</span>' ?></div>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="btn btn-dark" type="submit"><?= icon('save') ?>Save profile</button></div>
</form>
