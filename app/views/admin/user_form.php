<?php /** @var ?array $item @var bool $isNew @var array $errors */ $GLOBALS['_title'] = $isNew ? 'Add team member' : 'Edit team member'; ?>
<a class="crumb" href="<?= e(url('/admin/users')) ?>"><?= icon('left') ?>Team</a>
<div class="page-top"><div><h1><?= $isNew ? 'Add team member' : e($item['name']) ?></h1></div></div>
<form class="card" method="post" style="max-width:720px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="form-grid" style="grid-template-columns:1fr 1fr">
    <div class="fld"><label for="n">Name</label><input class="inp<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="n" name="name" value="<?= e($item['name'] ?? '') ?>" required><?php if (isset($errors['name'])): ?><span class="err"><?= e($errors['name']) ?></span><?php endif; ?></div>
    <div class="fld"><label for="em">Email (used to sign in)</label><input class="inp<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="em" name="email" type="email" value="<?= e($item['email'] ?? '') ?>" required><?php if (isset($errors['email'])): ?><span class="err"><?= e($errors['email']) ?></span><?php endif; ?></div>
    <div class="fld"><label for="r">Role</label>
      <select class="inp" id="r" name="role"><?php foreach (ROLES as $k => $l): ?><option value="<?= e($k) ?>"<?= ($item['role'] ?? 'agent') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <?php if (isset($errors['role'])): ?><span class="err"><?= e($errors['role']) ?></span><?php else: ?><span class="help">Agent: leads only. Editor: leads and website content. Administrator: everything.</span><?php endif; ?>
    </div>
    <div class="fld"><span class="label">Access</span><label class="switch"><input type="checkbox" name="active" value="1"<?= (int) ($item['active'] ?? 1) ? ' checked' : '' ?>><i></i>Can sign in</label></div>
    <div class="fld c2"><label for="pw"><?= $isNew ? 'Password' : 'New password (leave empty to keep the current one)' ?></label><input class="inp<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="pw" name="password" type="password" autocomplete="new-password"<?= $isNew ? ' required' : '' ?>><?php if (isset($errors['password'])): ?><span class="err"><?= e($errors['password']) ?></span><?php else: ?><span class="help">At least 10 characters with letters and numbers. Share it privately, and ask them to change it in Profile.</span><?php endif; ?></div>
  </div>
  <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px"><a class="btn btn-ghost" href="<?= e(url('/admin/users')) ?>">Cancel</a><button class="btn btn-dark" type="submit"><?= icon('save') ?><?= $isNew ? 'Add team member' : 'Save changes' ?></button></div>
</form>
