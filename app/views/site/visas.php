<?php /** @var array $list */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Visas</li></ol>
    <h1>Visas without the guesswork</h1>
    <p class="lead">Our visa team prepares your file, books appointments and checks every document before it is submitted.</p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap">
    <?php if ($list): ?>
    <table class="visa-table">
      <thead><tr><th scope="col">Country</th><th scope="col">Processing</th><th scope="col">Validity</th><th scope="col">Stay</th><th scope="col">Fee from</th><th><span class="sr-only">Details</span></th></tr></thead>
      <tbody>
      <?php foreach ($list as $v): ?>
        <tr>
          <td><a class="country" href="<?= e(url('/visas/' . $v['slug'])) ?>"><?= e($v['country']) ?><small><?= e($v['visa_type']) ?></small></a></td>
          <td><?= e($v['processing_time']) ?></td>
          <td><?= e($v['validity']) ?></td>
          <td><?= e($v['stay']) ?></td>
          <td class="fee"><?= $v['price'] ? e(price($v['price'])) : 'On request' ?></td>
          <td style="text-align:right"><a class="link" href="<?= e(url('/visas/' . $v['slug'])) ?>" aria-label="<?= e($v['country']) ?> visa requirements">Requirements<?= icon('arrow') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="visa-cards">
      <?php foreach ($list as $v): ?><a href="<?= e(url('/visas/' . $v['slug'])) ?>"><strong><?= e($v['country']) ?></strong><small><?= e($v['visa_type']) ?> · <?= e($v['processing_time']) ?></small><b><?= $v['price'] ? e(price($v['price'])) : '—' ?></b></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<section class="section section-sky">
  <div class="wrap contact-grid">
    <div>
      <h2 class="h-l">Need a visa for somewhere else?</h2>
      <p class="lead">We handle visas for many more countries. Tell us where you are going and your nationality, and we will send the exact checklist and fee.</p>
    </div>
    <div class="form-panel">
      <?= partial('enquiry_form', ['type' => 'visa', 'submit' => 'Check my visa', 'showPax' => false, 'extra' => [
          ['name' => 'subject', 'label' => 'Visa for (country)', 'placeholder' => 'e.g. Japan'],
          ['name' => 'details[Nationality]', 'label' => 'Your nationality', 'placeholder' => 'e.g. Indian'],
          ['name' => 'details[Residence]', 'label' => 'Country of residence', 'placeholder' => 'e.g. UAE'],
          ['name' => 'adults', 'label' => 'Applicants', 'type' => 'number', 'value' => '1'],
      ]]) ?>
    </div>
  </div>
</section>
