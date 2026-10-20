<?php /** @var ?array $lead */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head" style="min-height:70vh;display:flex;align-items:center">
  <div class="wrap">
    <h1>Request received.</h1>
    <?php if ($lead): ?>
      <p class="lead">Thank you<?= $lead['name'] ? ', ' . e(explode(' ', $lead['name'])[0]) : '' ?>. Your reference is <strong style="color:var(--orange)"><?= e($lead['ref']) ?></strong>. A consultant will contact you shortly<?= $lead['email'] ? ' and we have emailed you a copy' : '' ?>.</p>
      <div class="hero-actions" style="margin-top:28px">
        <a class="btn btn-wa" href="<?= e(wa_link(lead_whatsapp_message($lead))) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>Continue on WhatsApp</a>
        <a class="btn btn-ghost" style="color:#fff" href="<?= e(url('/packages')) ?>">Keep browsing</a>
      </div>
    <?php else: ?>
      <p class="lead">Thank you. A consultant will contact you shortly.</p>
      <a class="btn" href="<?= e(url('/')) ?>" style="margin-top:20px">Back to home</a>
    <?php endif; ?>
  </div>
</section>
