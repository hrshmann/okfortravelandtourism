<?php
/** @var array $res @var ?array $item @var array $errors @var bool $isNew */
$name = $res['name'];
$GLOBALS['_title'] = ($isNew ? 'New ' : 'Edit ') . $res['singular'];
$title = $item[$res['title']] ?? '';
$publicUrl = !$isNew && !empty($res['public']) && !empty($item['slug']) ? url(str_replace('{slug}', $item['slug'], $res['public'])) : null;
$slugPrefix = !empty($res['public']) ? rtrim(str_replace('{slug}', '', $res['public']), '/') . '/' : '/';
?>
<a class="crumb" href="<?= e(url('/admin/' . $name)) ?>"><?= icon('left') ?><?= e($res['label']) ?></a>
<div class="page-top">
  <div><h1><?= $isNew ? 'New ' . e($res['singular']) : e(str_limit((string) $title, 70)) ?></h1><?php if (!$isNew && !empty($item['updated_at'])): ?><p>Last saved <?= e(time_ago($item['updated_at'])) ?></p><?php endif; ?></div>
  <div class="actions"><?php if ($publicUrl): ?><a class="btn btn-ghost" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>View on website</a><?php endif; ?></div>
</div>

<?php if ($errors): ?><div class="alert error" role="alert"><?= icon('alert') ?><span>Check the highlighted fields: <?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

<form method="post" action="<?= e(url('/admin/' . $name . '/' . ($isNew ? 'new' : $item['id']))) ?>" data-dirty novalidate>
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-grid">
      <?php foreach ($res['fields'] as $f):
          if (isset($f['section'])): ?>
            <div class="form-section"><h3><?= e($f['section']) ?></h3><?php if (!empty($f['help'])): ?><p><?= e($f['help']) ?></p><?php endif; ?></div>
          <?php continue; endif;
          $n = $f['name'];
          $v = resource_form_value($f, $item);
          $id = 'f_' . $n;
          $err = $errors[$n] ?? null;
          $cls = 'fld c' . ($f['col'] ?? 4);
      ?>
        <div class="<?= $cls ?>">
          <?php if ($f['type'] !== 'toggle'): ?><label for="<?= e($id) ?>"><?= e($f['label']) ?><?= !empty($f['required']) ? ' <span style="color:var(--red)" aria-hidden="true">*</span>' : '' ?></label><?php endif; ?>
          <?php switch ($f['type']):
              case 'textarea':
              case 'lines': ?>
                <textarea class="inp<?= $err ? ' is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($n) ?>" rows="<?= (int) ($f['rows'] ?? 4) ?>"<?= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?= isset($f['counter']) ? ' data-counter="' . (int) $f['counter'] . '"' : '' ?>><?= e($v) ?></textarea>
              <?php break;
              case 'richtext': ?>
                <div class="rte" data-rte>
                  <div class="rte-bar" role="toolbar" aria-label="Formatting">
                    <button type="button" data-cmd="formatBlock" data-arg="h2" title="Heading" aria-label="Heading"><?= icon('heading') ?></button>
                    <button type="button" data-cmd="formatBlock" data-arg="h3" title="Subheading" aria-label="Subheading" style="font-weight:800;font-size:12px">H3</button>
                    <button type="button" data-cmd="formatBlock" data-arg="p" title="Paragraph" aria-label="Paragraph" style="font-weight:800">¶</button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="bold" title="Bold" aria-label="Bold"><?= icon('bold') ?></button>
                    <button type="button" data-cmd="italic" title="Italic" aria-label="Italic"><?= icon('italic') ?></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="insertUnorderedList" title="Bullet list" aria-label="Bullet list"><?= icon('list') ?></button>
                    <button type="button" data-cmd="insertOrderedList" title="Numbered list" aria-label="Numbered list"><?= icon('listol') ?></button>
                    <button type="button" data-cmd="formatBlock" data-arg="blockquote" title="Quote" aria-label="Quote"><?= icon('quote') ?></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="createLink" title="Link" aria-label="Add link"><?= icon('link') ?></button>
                    <button type="button" data-cmd="insertImage" title="Image" aria-label="Insert image"><?= icon('image') ?></button>
                    <button type="button" data-cmd="removeFormat" title="Clear formatting" aria-label="Clear formatting"><?= icon('undo') ?></button>
                    <span class="sep"></span>
                    <button type="button" data-cmd="source" title="Edit HTML" aria-label="Edit HTML" style="font-weight:800;font-size:11px">&lt;/&gt;</button>
                  </div>
                  <div class="rte-body<?= !empty($f['tall']) ? ' tall' : '' ?>" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="<?= e($id) ?>-l" data-placeholder="Start writing…"><?= (string) $v ?></div>
                  <textarea id="<?= e($id) ?>" name="<?= e($n) ?>"><?= e($v) ?></textarea>
                </div>
                <span id="<?= e($id) ?>-l" class="sr-only"><?= e($f['label']) ?></span>
              <?php break;
              case 'toggle': ?>
                <label class="switch"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($n) ?>" value="1"<?= (int) $v ? ' checked' : '' ?>><i></i><?= e($f['label']) ?></label>
              <?php break;
              case 'select': ?>
                <select class="inp" id="<?= e($id) ?>" name="<?= e($n) ?>"><?php foreach ($f['options'] as $ok => $ol): ?><option value="<?= e($ok) ?>"<?= (string) $v === (string) $ok ? ' selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
              <?php break;
              case 'relation': $opts = rows("SELECT id, {$f['display']} AS label FROM {$f['table']} ORDER BY {$f['display']}"); ?>
                <select class="inp" id="<?= e($id) ?>" name="<?= e($n) ?>"><option value="">— None —</option><?php foreach ($opts as $o): ?><option value="<?= (int) $o['id'] ?>"<?= (int) $v === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['label']) ?></option><?php endforeach; ?></select>
              <?php break;
              case 'image': ?>
                <div class="img-field" data-image-field>
                  <img class="preview" src="<?= e($v ? img_w($v, 300) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=') ?>" alt="">
                  <div class="controls">
                    <input class="inp<?= $err ? ' is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($n) ?>" value="<?= e($v) ?>" placeholder="Choose from the library or paste an image link">
                    <div class="row"><button type="button" class="btn btn-ghost btn-sm" data-pick-image><?= icon('image') ?>Choose image</button><?php if ($v): ?><button type="button" class="btn btn-ghost btn-sm" data-clear-image>Remove</button><?php endif; ?></div>
                  </div>
                </div>
              <?php break;
              case 'gallery': ?>
                <div class="gallery-field" data-gallery data-name="<?= e($n) ?>[]">
                  <?php foreach ((array) $v as $g): ?><div class="g-item"><img src="<?= e(img_w($g, 240)) ?>" alt=""><input type="hidden" name="<?= e($n) ?>[]" value="<?= e($g) ?>"><button type="button" data-remove aria-label="Remove photo">×</button></div><?php endforeach; ?>
                  <button type="button" class="g-add" data-pick-gallery><?= icon('plus') ?>Add photos</button>
                </div>
              <?php break;
              case 'itinerary': $days = (array) $v ?: [['title' => '', 'text' => '']]; ?>
                <div class="repeater" data-repeater>
                  <?php foreach ($days as $i => $d): ?>
                    <div class="rep-item">
                      <span class="n" data-n><?= $i + 1 ?></span>
                      <div class="fields"><input class="inp" name="<?= e($n) ?>_title[]" value="<?= e($d['title'] ?? '') ?>" placeholder="Day title, e.g. Arrive in Dubai" aria-label="Day title"><textarea class="inp" name="<?= e($n) ?>_text[]" rows="2" placeholder="What happens on this day" aria-label="Day description"><?= e($d['text'] ?? '') ?></textarea></div>
                      <div class="tools"><button type="button" class="icon-btn" data-up aria-label="Move up"><?= icon('up') ?></button><button type="button" class="icon-btn" data-down aria-label="Move down"><?= icon('down') ?></button><button type="button" class="icon-btn danger" data-remove aria-label="Remove day"><?= icon('trash') ?></button></div>
                    </div>
                  <?php endforeach; ?>
                  <button type="button" class="btn btn-ghost btn-sm" data-add style="justify-self:start"><?= icon('plus') ?>Add day</button>
                </div>
              <?php break;
              case 'slug': ?>
                <div class="slug-wrap"><span><?= e($slugPrefix) ?></span><input id="<?= e($id) ?>" name="<?= e($n) ?>" value="<?= e($v) ?>" data-slug-from="f_<?= e($f['source']) ?>"<?= $isNew ? '' : ' data-locked' ?> placeholder="auto"></div>
              <?php break;
              case 'datetime': ?>
                <input class="inp" id="<?= e($id) ?>" name="<?= e($n) ?>" type="datetime-local" value="<?= e($v) ?>">
              <?php break;
              case 'number':
              case 'money': ?>
                <input class="inp<?= $err ? ' is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($n) ?>" type="number" value="<?= e($v) ?>" step="<?= e($f['step'] ?? ($f['type'] === 'money' ? '0.01' : '1')) ?>"<?= isset($f['min']) ? ' min="' . e($f['min']) . '"' : '' ?><?= isset($f['max']) ? ' max="' . e($f['max']) . '"' : '' ?><?= !empty($f['required']) ? ' required' : '' ?>>
              <?php break;
              default: ?>
                <input class="inp<?= $err ? ' is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($n) ?>" value="<?= e($v) ?>"<?= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?= isset($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '' ?><?= !empty($f['required']) ? ' required' : '' ?><?= isset($f['counter']) ? ' data-counter="' . (int) $f['counter'] . '"' : '' ?><?= isset($f['datalist']) ? ' list="dl_' . e($n) . '"' : '' ?>>
                <?php if (isset($f['datalist'])): ?><datalist id="dl_<?= e($n) ?>"><?php foreach (datalist_values($f['datalist']) as $o): ?><option value="<?= e($o) ?>"><?php endforeach; ?></datalist><?php endif; ?>
          <?php endswitch; ?>
          <?php if ($err): ?><span class="err"><?= e($err) ?></span><?php elseif (!empty($f['help'])): ?><span class="help"><?= e($f['help']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-actions">
    <span class="muted small" data-dirty-note>All changes saved.</span>
    <div class="right">
      <a class="btn btn-ghost" href="<?= e(url('/admin/' . $name)) ?>">Cancel</a>
      <button class="btn btn-dark" type="submit"><?= icon('save') ?><?= $isNew ? 'Create ' . e($res['singular']) : 'Save changes' ?></button>
    </div>
  </div>
</form>
