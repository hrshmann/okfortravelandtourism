<?php /** @var array $page */ $GLOBALS['_title'] = 'Media library'; ?>
<div class="page-top"><div><h1>Media library</h1><p><?= plural($page['total'], 'image') ?>. Uploads are resized to 2000px and converted to WebP to keep pages fast.</p></div></div>
<form method="post" action="<?= e(url('/admin/media/upload')) ?>" enctype="multipart/form-data" data-upload-form>
  <?= csrf_field() ?>
  <label class="dropzone" data-dropzone>
    <input type="file" name="files[]" accept="image/*" multiple hidden data-upload-input>
    <?= icon('upload') ?><br><strong>Drop images here or click to upload</strong><br><span class="small">JPG, PNG, WebP or GIF up to 12 MB each</span>
  </label>
  <noscript><button class="btn" type="submit">Upload</button></noscript>
</form>
<div class="media-grid">
  <?php foreach ($page['items'] as $m): ?>
    <div class="media-item" style="cursor:default">
      <img src="<?= e(img($m['path'])) ?>" alt="<?= e($m['alt'] ?: $m['original_name']) ?>" loading="lazy">
      <span class="meta"><?= e($m['original_name']) ?> · <?= (int) $m['width'] ?>×<?= (int) $m['height'] ?></span>
      <div style="position:absolute;top:6px;right:6px;display:flex;gap:4px">
        <button type="button" class="icon-btn" data-copy="<?= e($m['path']) ?>" title="Copy path" aria-label="Copy image path"><?= icon('copy') ?></button>
        <form method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="icon-btn danger" title="Delete" aria-label="Delete image" data-confirm="Delete this image? Pages that use it will show a placeholder."><?= icon('trash') ?></button></form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$page['items']): ?><p class="muted" style="text-align:center;padding:30px">No images yet. Starter content uses free Unsplash photos — upload your own to replace them.</p><?php endif; ?>
<?= pager($page) ?>
