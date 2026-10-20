<?php $n = max(0, min(5, (int) round((float) ($rating ?? 5)))); ?>
<span class="stars" role="img" aria-label="<?= $n ?> out of 5 stars"><?= str_repeat(icon('star'), $n) ?></span>
