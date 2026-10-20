<?php /** @var bool $sent */
ob_start(); ?>
<div class="box">
  <h1>Reset password</h1>
  <?php if ($sent): ?>
    <div class="alert success"><?= icon('checkcircle') ?><span>If that email belongs to an account, a reset link is on its way. It works for one hour.</span></div>
    <p><a href="<?= e(url('/admin/login')) ?>">Back to sign in</a></p>
  <?php else: ?>
    <p class="muted" style="margin:0 0 22px">Enter your account email and we will send you a link to choose a new password.</p>
    <form method="post" action="<?= e(url('/admin/forgot')) ?>">
      <?= csrf_field() ?>
      <div class="fld"><label for="email">Email</label><input class="inp" id="email" name="email" type="email" required autocomplete="username" autofocus></div>
      <button class="btn btn-dark btn-block" type="submit" style="min-height:46px">Send reset link</button>
      <p style="margin-top:18px;text-align:center"><a href="<?= e(url('/admin/login')) ?>">Back to sign in</a></p>
    </form>
  <?php endif; ?>
</div>
<?php echo render('admin/_auth', ['inner' => ob_get_clean(), 'pageTitle' => 'Reset password']);
