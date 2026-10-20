<?php /** @var string $type */ $GLOBALS['_layout_dark'] = true; $map = trim((string) setting('map_query', '')); ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Contact</li></ol>
    <h1>Let’s plan your trip</h1>
    <p class="lead">Send a request and a consultant will reply with options and a clear price. Prefer to talk? WhatsApp or call us.</p>
  </div>
</section>
<section class="section">
  <div class="wrap contact-grid">
    <div>
      <div class="contact-cards">
        <a class="contact-card wa" href="<?= e(wa_link('Hello! I would like help planning a trip.')) ?>" target="_blank" rel="noopener" data-wa="contact"><span class="ic"><?= icon('whatsapp') ?></span><span><small>WhatsApp — fastest</small><strong><?= e(setting('phone')) ?></strong></span></a>
        <a class="contact-card" href="<?= e(tel_link(setting('phone'))) ?>"><span class="ic"><?= icon('phone') ?></span><span><small>Call us</small><strong><?= e(setting('phone')) ?></strong></span></a>
        <a class="contact-card" href="mailto:<?= e(setting('email')) ?>"><span class="ic"><?= icon('mail') ?></span><span><small>Email</small><strong><?= e(setting('email')) ?></strong></span></a>
        <div class="contact-card"><span class="ic"><?= icon('pin') ?></span><span><small><?= e(setting('office_hours')) ?></small><strong><?= e(setting('address')) ?></strong></span></div>
      </div>
      <?php if ($map !== ''): ?>
        <div class="map"><iframe title="Map to our office" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=<?= e(rawurlencode($map)) ?>&output=embed"></iframe></div>
      <?php endif; ?>
    </div>
    <div class="form-panel">
      <h2 class="h-s">Send a request</h2>
      <p class="muted small">We reply within working hours, usually within the hour.</p>
      <?= partial('enquiry_form', ['type' => $type, 'submit' => 'Send request', 'extra' => [
          ['name' => 'type', 'label' => 'I need help with', 'type' => 'select', 'options' => array_values(LEAD_TYPES), 'value' => LEAD_TYPES[$type] ?? '', 'full' => true],
      ]]) ?>
    </div>
  </div>
</section>
