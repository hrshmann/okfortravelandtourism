<section class="section-tight" style="padding-bottom:0">
  <div class="wrap">
    <div class="cta-band">
      <svg class="swoosh" viewBox="0 0 600 300" aria-hidden="true"><path d="M10 290 C 200 250, 380 190, 590 10" fill="none" stroke="#000C64" stroke-width="10" stroke-linecap="round" opacity=".14"/><path d="M60 295 C 240 260, 420 200, 600 40" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".35"/></svg>
      <div>
        <h2><?= e($ctaTitle ?? 'Tell us where. We handle the rest.') ?></h2>
        <p><?= e($ctaText ?? 'Send your dates and number of travellers. A consultant comes back with options and a clear price — usually within the hour.') ?></p>
      </div>
      <div class="actions">
        <a class="btn btn-dark" href="<?= e(url('/contact', ['type' => 'holiday'])) ?>">Plan my trip</a>
        <a class="btn btn-ghost" href="<?= e(wa_link('Hello! I would like help planning a trip.')) ?>" target="_blank" rel="noopener" data-wa="holiday"><?= icon('whatsapp') ?>WhatsApp us</a>
      </div>
    </div>
  </div>
</section>
