<?php
/** @var array<string, mixed> $b */
$kat = config('Silsilah')->kategoriBerita[$b['kategori']] ?? ['label' => $b['kategori'], 'ikon' => 'bi-newspaper'];
?>
<a href="<?= site_url('berita/' . $b['slug']) ?>" class="card kartu-berita text-decoration-none text-body">
    <div class="gambar">
        <?php if ($b['gambar']) : ?>
            <img src="<?= base_url('uploads/konten/' . $b['gambar']) ?>" alt="" loading="lazy">
        <?php else : ?>
            <i class="bi <?= $kat['ikon'] ?>"></i>
        <?php endif ?>
    </div>
    <div class="card-body">
        <div class="kategori kategori-<?= esc($b['kategori'], 'attr') ?> mb-1"><?= esc($kat['label']) ?> · <span class="text-teks-2 fw-normal text-lowercase"><?= esc(tanggal_indo($b['terbit_at'])) ?></span></div>
        <h3 class="h5 mb-2"><?= esc($b['judul']) ?></h3>
        <?php if ($b['ringkasan']) : ?><p class="small text-teks-2 mb-0"><?= esc(mb_strimwidth($b['ringkasan'], 0, 140, '…')) ?></p><?php endif ?>
    </div>
</a>
