<?php /** @var array $faqs */ $GLOBALS['_layout_dark'] = true;
$groups = [];
foreach ($faqs as $f) {
    $groups[$f['category'] ?: 'General'][] = $f;
}
?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>FAQ</li></ol>
    <h1>Questions, answered</h1>
    <p class="lead">How booking, payments, visas and changes work. Can’t find your answer? Message us.</p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap faq-layout">
    <div>
      <nav aria-label="Topics" class="filters" style="position:sticky;display:grid">
        <ul class="filter-list"><?php foreach (array_keys($groups) as $g): ?><li><a href="#<?= e(slugify($g)) ?>"><?= e($g) ?><small><?= count($groups[$g]) ?></small></a></li><?php endforeach; ?></ul>
        <a class="btn btn-wa" href="<?= e(wa_link('Hello! I have a question.')) ?>" target="_blank" rel="noopener" data-wa="contact"><?= icon('whatsapp') ?>Ask on WhatsApp</a>
      </nav>
    </div>
    <div>
      <?php foreach ($groups as $g => $items): ?>
        <h2 class="h-s" id="<?= e(slugify($g)) ?>" style="margin-top:12px"><?= e($g) ?></h2>
        <div class="faq" style="margin-bottom:40px"><?php foreach ($items as $f): ?><details><summary><?= e($f['question']) ?><span class="pm"><?= icon('plus') ?></span></summary><div class="answer"><?= nl2br(e($f['answer'])) ?></div></details><?php endforeach; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?= partial('cta') ?>
