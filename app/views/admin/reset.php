<?php /** @var bool $valid @var ?string $error */
ob_start(); ?>
<div class="box">
  <h1>Choose a new password</h1>
  <?php if (!$valid): ?>
    <div class="alert error"><?= icon('alert') ?><span>This reset link has expired or was already used. Request a new one.</span></div>
    <p><a href="<?= e(url('/admin/forgot')) ?>">Request a new link</a></p>
  <?php else: ?>
    <?php if ($error): ?><div class="alert error" role="alert"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="fld"><label for="password">New password</label><div class="pw-wrap"><input class="inp" id="password" name="password" type="password" required minlength="10" autocomplete="new-password" autofocus><button type="button" data-reveal="password" aria-pressed="false" aria-label="Show password">Show</button></div><span class="help">At least 10 characters, with letters and numbers.</span></div>
      <div class="fld"><label for="password2">Repeat password</label><input class="inp" id="password2" name="password2" type="password" required autocomplete="new-password"></div>
      <button class="btn btn-dark btn-block" type="submit" style="min-height:46px">Save password</button>
    </form>
  <?php endif; ?>
</div>
<?php echo render('admin/_auth', ['inner' => ob_get_clean(), 'pageTitle' => 'New password']);
