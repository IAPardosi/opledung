<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Halaman">
    <ul class="pagination pagination-sm flex-wrap">
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="Pertama">&laquo;</a></li>
            <li class="page-item"><a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Sebelumnya">&lsaquo;</a></li>
        <?php endif ?>
        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>"><a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a></li>
        <?php endforeach ?>
        <?php if ($pager->hasNext()) : ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Berikutnya">&rsaquo;</a></li>
            <li class="page-item"><a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Terakhir">&raquo;</a></li>
        <?php endif ?>
    </ul>
</nav>
