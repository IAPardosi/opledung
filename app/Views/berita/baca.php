<?php $kat = config('Silsilah')->kategoriBerita[$b['kategori']] ?? ['label' => $b['kategori'], 'ikon' => 'bi-newspaper']; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($b['judul']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 820px">
    <a href="<?= site_url('berita') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Semua berita</a>
    <div class="kategori kategori-<?= esc($b['kategori'], 'attr') ?> mt-3"><i class="bi <?= $kat['ikon'] ?>"></i> <?= esc($kat['label']) ?></div>
    <h1 class="display-6 mt-1 mb-2"><?= esc($b['judul']) ?></h1>
    <p class="text-teks-2 small mb-4"><?= esc(tanggal_indo($b['terbit_at'])) ?> · <?= number_format((int) $b['dilihat'] + 1, 0, ',', '.') ?> kali dibaca</p>
    <?php if ($b['gambar']) : ?>
        <img src="<?= base_url('uploads/konten/' . $b['gambar']) ?>" class="img-fluid rounded-4 mb-4 w-100" alt="">
    <?php endif ?>
    <?php if ($b['ringkasan']) : ?><p class="lead"><?= esc($b['ringkasan']) ?></p><?php endif ?>
    <div class="isi-artikel"><?= format_isi($b['isi']) ?></div>
    <div class="ipon-kecil my-5 rounded"></div>
    <?php if ($lainnya !== []) : ?>
        <div class="judul-bagian"><h2 class="h4">Berita lainnya</h2></div>
        <div class="row g-3">
            <?php foreach ($lainnya as $l) : ?><div class="col-md-4"><?= view('partials/kartu_berita', ['b' => $l]) ?></div><?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
