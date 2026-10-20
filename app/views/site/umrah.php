<?php /** @var array $packages @var array $faqs */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head has-img">
  <img src="<?= e(img_w('https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=2000&q=80', 2000)) ?>" alt="" width="2000" height="1200" fetchpriority="high">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Umrah &amp; Hajj</li></ol>
    <h1>Umrah, handled with care</h1>
    <p class="lead">Visas, hotels close to the Haram, transport between the holy cities and guided Ziyarat — arranged by a team that stays with you throughout.</p>
    <div class="hero-actions" style="margin-top:24px">
      <a class="btn" href="#quote">Get an Umrah quote</a>
      <a class="btn btn-ghost" style="color:#fff" href="<?= e(wa_link('Assalamu alaikum! I would like details about Umrah packages.')) ?>" target="_blank" rel="noopener" data-wa="umrah"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <ul class="steps plain">
      <li><?= icon('passport') ?><h3>Visa arranged</h3><p>Tourist e-visa or Umrah visa, whichever fits your nationality, processed with your package.</p></li>
      <li><?= icon('hotel') ?><h3>Hotels near the Haram</h3><p>Walking distance in both Makkah and Madinah, from comfortable to five-star.</p></li>
      <li><?= icon('car') ?><h3>Transport included</h3><p>Airport transfers and travel between Makkah and Madinah by private car or coach.</p></li>
      <li><?= icon('users') ?><h3>Guided Ziyarat</h3><p>Visits to the historic sites of both cities with a knowledgeable guide.</p></li>
    </ul>
  </div>
</section>

<section class="section section-sky" style="padding-top:clamp(56px,6vw,88px)">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-l">Umrah packages</h2><p>Prices are per person. Every package can be adjusted — nights, hotel category and departure city.</p></div></div>
    <?php if ($packages): ?>
      <div class="pkg-grid"><?php foreach ($packages as $p): ?><?= partial('pkg_card', ['p' => $p, 'withSummary' => true]) ?><?php endforeach; ?></div>
    <?php else: ?>
      <div class="empty"><h3>New Umrah dates are being added</h3><p>Send a request below and we will share the next available groups and private options.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="section" id="quote">
  <div class="wrap contact-grid">
    <div>
      <h2 class="h-l">Get a quote for your Umrah</h2>
      <p class="lead">Tell us when you would like to travel and how many people are going. We reply with hotel options and a full price.</p>
      <ul class="checks" style="margin-top:24px">
        <li><?= icon('check') ?>Private or group, from economy to VIP</li>
        <li><?= icon('check') ?>Departures from any city — flights can be included</li>
        <li><?= icon('check') ?>Special care for elderly pilgrims and families</li>
        <li><?= icon('check') ?>Instalments available on selected packages</li>
      </ul>
    </div>
    <div class="form-panel">
      <?= partial('enquiry_form', ['type' => 'umrah', 'subject' => 'Umrah quote', 'submit' => 'Get my Umrah quote', 'extra' => [
          ['name' => 'details[Package level]', 'label' => 'Package level', 'type' => 'select', 'options' => ['Economy', 'Premium', 'VIP', 'Hajj']],
          ['name' => 'details[Departure city]', 'label' => 'Departing from', 'placeholder' => 'e.g. Dubai, Karachi'],
      ], 'placeholder' => 'Number of nights in Makkah and Madinah, hotel preferences, special needs…']) ?>
    </div>
  </div>
</section>

<?php $umrahFaqs = array_values(array_filter($faqs, fn($f) => in_array($f['category'], ['Umrah', 'Booking', 'Payments'], true))); ?>
<?php if ($umrahFaqs): ?>
<section class="section section-sky">
  <div class="wrap faq-layout">
    <div><h2 class="h-l">Common questions</h2></div>
    <div class="faq"><?php foreach (array_slice($umrahFaqs, 0, 6) as $f): ?><details><summary><?= e($f['question']) ?><span class="pm"><?= icon('plus') ?></span></summary><div class="answer"><?= nl2br(e($f['answer'])) ?></div></details><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
