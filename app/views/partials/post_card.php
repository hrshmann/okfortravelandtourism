<?php /** @var array $post */ ?>
<a class="post-card" href="<?= e(url('/blog/' . $post['slug'])) ?>">
  <div class="media"><img src="<?= e(img_w($post['cover_image'], 800)) ?>" alt="" loading="lazy" width="800" height="500"></div>
  <div class="post-meta"><?php if ($post['category']): ?><span><?= e($post['category']) ?></span><?php endif; ?><span><?= reading_minutes($post['body']) ?> min read</span></div>
  <h3><?= e($post['title']) ?></h3>
  <?php if ($post['excerpt']): ?><p><?= e($post['excerpt']) ?></p><?php endif; ?>
</a>
