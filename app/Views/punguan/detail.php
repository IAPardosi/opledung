<?php use App\Models\PunguanModel; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($p['nama']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 1000px">
    <a href="<?= site_url('punguan') ?>" class="small"><i class="bi bi-arrow-left"></i> Semua punguan</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2 mb-4">
        <div>
            <span class="chip mb-2"><?= esc(PunguanModel::TINGKAT[$p['tingkat']]) ?></span>
            <h1 class="h2 mb-1"><?= esc($p['nama']) ?></h1>
            <p class="text-teks-2 mb-0"><?= esc($p['keterangan'] ?? '') ?></p>
        </div>
        <div class="stat text-end"><div class="angka"><?= number_format($jumlah, 0, ',', '.') ?></div><div class="label">member terdaftar</div></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Agenda punguan</div>
                <div class="card-body py-2">
                    <?php if ($kegiatan === []) : ?><p class="text-teks-2 my-2 small">Belum ada kegiatan terjadwal.</p><?php endif ?>
                    <?php foreach ($kegiatan as $i => $k) : ?>
                        <?php if ($i > 0) : ?><hr class="my-1"><?php endif ?>
                        <?= view('partials/baris_kegiatan', ['k' => $k]) ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-body">
                <div class="fw-bold mb-2">Penatua punguan</div>
                <?php if ($penatua === []) : ?><p class="small text-teks-2 mb-0">Belum ditetapkan.</p><?php endif ?>
                <?php foreach ($penatua as $pt) : ?>
                    <div class="d-flex gap-2 align-items-center mb-2"><span class="avatar avatar-sm"><?= esc(inisial($pt['nama_lengkap'] ?? $pt['username'])) ?></span><span><?= esc($pt['nama_lengkap'] ?? '@' . $pt['username']) ?></span></div>
                <?php endforeach ?>
                <?php if ($p['kontak']) : ?><div class="small text-teks-2 mt-2">Kontak: <?= esc($p['kontak']) ?></div><?php endif ?>
                <a href="<?= site_url('register') ?>" class="btn btn-utama mt-3">Daftarkan keluarga di punguan ini</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
