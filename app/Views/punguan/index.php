<?php use App\Models\PunguanModel; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Punguan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <p class="eyebrow mb-1">Satu tarombo, banyak daerah</p>
    <div class="judul-bagian"><h1 class="h2">Punguan <?= esc($marga['nama']) ?></h1></div>
    <p class="text-teks-2 mb-4" style="max-width: 44rem">Silsilah tetap satu pohon untuk seluruh pomparan. Punguan adalah organisasi daerah tempat anggota berkumpul, dan penatuanya mengesahkan pendaftaran keluarga di daerahnya.</p>
    <div class="row g-3">
        <?php foreach ($rows as $p) : ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= site_url('punguan/' . $p['slug']) ?>" class="card card-body h-100 text-body">
                    <span class="chip mb-3 align-self-start"><?= esc(PunguanModel::TINGKAT[$p['tingkat']]) ?></span>
                    <div class="h4 mb-1"><?= esc($p['nama']) ?></div>
                    <div class="small text-teks-2 mb-3"><?= esc($p['keterangan'] ?? '') ?></div>
                    <div class="stat mt-auto"><div class="angka"><?= number_format($p['jumlah'], 0, ',', '.') ?></div><div class="label">member terdaftar</div></div>
                </a>
            </div>
        <?php endforeach ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-body h-100 kartu-lembut">
                <div class="h5">Punguan lain menyusul</div>
                <p class="small text-teks-2 mb-0">Jabodetabek, Batam, dan punguan global (luar negeri) dapat ditambahkan pengurus pusat. Semua tetap tercatat di satu database pusat.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
