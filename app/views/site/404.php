<?php $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head" style="min-height:70vh;display:flex;align-items:center">
  <div class="wrap">
    <p class="board-title" style="color:var(--orange);margin-bottom:18px"><?= icon('alert') ?>Flight not found</p>
    <h1>This page has left the gate.</h1>
    <p class="lead">The link may be old or mistyped. Try one of these instead, or tell us what you were looking for.</p>
    <div class="hero-actions" style="margin-top:28px">
      <a class="btn" href="<?= e(url('/')) ?>">Back to home</a>
      <a class="btn btn-ghost" href="<?= e(url('/packages')) ?>" style="color:#fff">Browse holidays</a>
      <a class="btn btn-ghost" href="<?= e(wa_link('Hello! I could not find a page on your website.')) ?>" data-wa="contact" target="_blank" rel="noopener" style="color:#fff"><?= icon('whatsapp') ?>Ask us</a>
    </div>
  </div>
</section>
