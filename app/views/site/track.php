<?php /** @var string $ref @var string $contact @var ?array $lead @var ?string $error @var array $timeline */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Track a request</li></ol>
    <h1>Track your request</h1>
    <p class="lead">Enter the reference from your confirmation (for example OK2610-ABCDE) and the phone number or email you used.</p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap wrap-narrow">
    <form class="track-card" method="get" action="<?= e(url('/track')) ?>">
      <div class="form-grid">
        <div class="inp-wrap"><label for="tref">Reference</label><input class="inp" id="tref" name="ref" value="<?= e($ref) ?>" required placeholder="OK2610-ABCDE" autocapitalize="characters"></div>
        <div class="inp-wrap"><label for="tcontact">Phone or email</label><input class="inp" id="tcontact" name="contact" value="<?= e($contact) ?>" required></div>
      </div>
      <button class="btn" type="submit" style="margin-top:14px">Find my request</button>
      <?php if ($error): ?><p class="notice" style="margin:16px 0 0" role="alert"><?= icon('info') ?><?= e($error) ?></p><?php endif; ?>
    </form>

    <?php if ($lead): ?>
      <div class="track-card" style="margin-top:20px">
        <div class="listing-bar" style="margin-bottom:6px"><h2 class="h-s" style="margin:0"><?= e($lead['subject'] ?: lead_type_label($lead['type'])) ?></h2><span class="badge-status"><?= e(lead_status_label($lead['status'], true)) ?></span></div>
        <p class="muted">Reference <?= e($lead['ref']) ?> · sent <?= e(fmt_date($lead['created_at'], 'j M Y, H:i')) ?></p>
        <ol class="timeline">
          <li><span class="dot"><?= icon('check') ?></span><div><strong>Request received</strong><br><small><?= e(fmt_date($lead['created_at'], 'j M Y, H:i')) ?></small></div></li>
          <?php foreach ($timeline as $t): ?>
            <li><span class="dot"><?= icon('check') ?></span><div><strong><?= e($t['body']) ?></strong><br><small><?= e(fmt_date($t['created_at'], 'j M Y, H:i')) ?></small></div></li>
          <?php endforeach; ?>
        </ol>
        <a class="btn btn-wa" href="<?= e(wa_link('Hello! I am following up on my request ' . $lead['ref'] . '.')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Message your consultant</a>
      </div>
    <?php endif; ?>
  </div>
</section>
