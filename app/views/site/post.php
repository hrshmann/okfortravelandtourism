<?php /** @var array $post @var array $more */ $GLOBALS['_layout_dark'] = true; ?>
<section class="page-head<?= $post['cover_image'] ? ' has-img' : '' ?>">
  <?php if ($post['cover_image']): ?><img src="<?= e(img_w($post['cover_image'], 2000)) ?>" alt="" width="2000" height="1200" fetchpriority="high"><?php endif; ?>
  <div class="wrap">
    <ol class="crumbs"><li><a href="<?= e(url('/')) ?>">Home</a></li><li><a href="<?= e(url('/blog')) ?>">Travel journal</a></li><?php if ($post['category']): ?><li><a href="<?= e(url('/blog', ['category' => $post['category']])) ?>"><?= e($post['category']) ?></a></li><?php endif; ?></ol>
    <?php if ($post['status'] !== 'published'): ?><p class="tag tag-orange" style="margin-bottom:14px">Draft — only admins can see this page</p><?php endif; ?>
    <h1 style="max-width:22ch;font-size:var(--s-56)"><?= e($post['title']) ?></h1>
    <div class="head-meta">
      <?php if ($post['published_at']): ?><span class="tag"><?= icon('calendar') ?><?= e(fmt_date($post['published_at'])) ?></span><?php endif; ?>
      <span class="tag"><?= icon('clock') ?><?= reading_minutes($post['body']) ?> min read</span>
      <?php if ($post['author']): ?><span class="tag"><?= icon('user') ?><?= e($post['author']) ?></span><?php endif; ?>
    </div>
  </div>
</section>
<article class="section-tight">
  <div class="wrap wrap-narrow">
    <?php if ($post['excerpt']): ?><p class="lead" style="color:var(--ink);margin-bottom:1.6em"><?= e($post['excerpt']) ?></p><?php endif; ?>
    <div class="prose"><?= $post['body'] ?></div>
    <div class="cta-band" style="margin-top:48px;grid-template-columns:1fr;padding:clamp(28px,4vw,44px)">
      <div><h2 style="font-size:var(--s-33)">Have a question about this?</h2><p>Our consultants answer on WhatsApp — usually within minutes.</p></div>
      <div class="actions"><a class="btn btn-dark" href="<?= e(wa_link('Hello! I read "' . $post['title'] . '" and have a question.')) ?>" target="_blank" rel="noopener" data-wa="contact"><?= icon('whatsapp') ?>Ask on WhatsApp</a></div>
    </div>
  </div>
</article>
<?php if ($more): ?>
<section class="section section-sky">
  <div class="wrap">
    <div class="sec-head"><div><h2 class="h-m">Keep reading</h2></div><a class="link" href="<?= e(url('/blog')) ?>">All articles<?= icon('arrow') ?></a></div>
    <div class="posts"><?php foreach ($more as $m): ?><?= partial('post_card', ['post' => $m]) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
