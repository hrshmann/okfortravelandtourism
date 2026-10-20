<?php /** @var ?string $error @var string $email */
ob_start(); ?>
<form method="post" action="<?= e(url('/admin/login')) ?>">
  <?= csrf_field() ?>
  <h1>Sign in</h1>
  <p class="muted" style="margin:0 0 22px">Admin panel for <?= e(setting('site_name')) ?>.</p>
  <?php foreach (flashes() as $f): ?><div class="alert <?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
  <?php if ($error): ?><div class="alert error" role="alert"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
  <div class="fld"><label for="email">Email</label><input class="inp" id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="username" autofocus></div>
  <div class="fld">
    <div class="auth-row" style="margin:0"><label for="password" style="font-size:13px;font-weight:700">Password</label><a href="<?= e(url('/admin/forgot')) ?>">Forgot password?</a></div>
    <div class="pw-wrap"><input class="inp" id="password" name="password" type="password" required autocomplete="current-password"><button type="button" data-reveal="password" aria-pressed="false" aria-label="Show password">Show</button></div>
  </div>
  <button class="btn btn-dark btn-block" type="submit" style="min-height:48px;margin-top:6px">Sign in</button>
</form>
<?php echo render('admin/_auth', ['inner' => ob_get_clean(), 'pageTitle' => 'Sign in']);
