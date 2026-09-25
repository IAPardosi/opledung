<?php if (session('error') !== null) : ?>
    <div class="alert alert-danger small"><?= esc(session('error')) ?></div>
<?php elseif (session('errors') !== null) : ?>
    <div class="alert alert-danger small">
        <?php foreach ((array) session('errors') as $e) : ?><?= esc($e) ?><br><?php endforeach ?>
    </div>
<?php endif ?>
<?php if (session('message') !== null) : ?>
    <div class="alert alert-success small"><?= esc(session('message')) ?></div>
<?php endif ?>
