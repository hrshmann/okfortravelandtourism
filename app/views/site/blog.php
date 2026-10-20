<?php /** @var array $page @var string $cat @var array $categories */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head">
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li>Travel journal</li></ol>
    <h1>Travel journal</h1>
    <p class="lead">Visa guides, Umrah checklists and honest destination advice from the people who plan these trips every day.</p>
  </div>
</section>
<section class="section-tight">
  <div class="wrap">
    <?php if ($categories): ?>
      <ul class="filter-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:32px">
        <li><a href="<?= e(url('/blog')) ?>"<?= $cat === '' ? ' aria-current="true"' : '' ?>>All</a></li>
        <?php foreach ($categories as $c): ?><li><a href="<?= e(url('/blog', ['category' => $c])) ?>"<?= $cat === $c ? ' aria-current="true"' : '' ?>><?= e($c) ?></a></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($page['items']): ?>
      <div class="posts"><?php foreach ($page['items'] as $post): ?><?= partial('post_card', ['post' => $post]) ?><?php endforeach; ?></div>
      <?= pager($page, ['category' => $cat ?: null]) ?>
    <?php else: ?>
      <div class="empty"><h3>No articles here yet</h3><p>New guides are on the way. In the meantime, our consultants can answer any question on WhatsApp.</p></div>
    <?php endif; ?>
  </div>
</section>
