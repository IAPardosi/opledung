<?php
use App\Models\KeanggotaanPunguanModel;
use App\Services\KeuanganService;
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Keuangan <?= esc($k['nama_lengkap']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 980px">
    <a href="<?= site_url('admin/keuangan?punguan=' . $k['punguan_id']) ?>" class="small"><i class="bi bi-arrow-left"></i> Keuangan <?= esc($k['nama_punguan']) ?></a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><?= esc($k['nama_lengkap']) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?= badge_member(['status' => $k['status'], 'punguan' => $k['nama_punguan']]) ?>
                <span class="small text-teks-2">No. <?= esc($k['nomor_anggota'] ?? '–') ?> · <?= esc(KeanggotaanPunguanModel::STATUS[$k['status']]) ?><?= $k['tanggal_masuk'] ? ' sejak ' . esc(tanggal_indo($k['tanggal_masuk'])) : '' ?></span>
            </div>
        </div>
        <?php if ($k['status'] === 'aktif') : ?>
            <a class="btn btn-utama" href="<?= site_url('admin/keuangan/catat?punguan=' . $k['punguan_id'] . '&anggota=' . $k['id']) ?>"><i class="bi bi-plus-lg"></i> Catat pembayaran</a>
        <?php endif ?>
    </div>
    <?= view('partials/tabel_keuangan', ['riwayat' => $riwayat]) ?>
</div>
<?= $this->endSection() ?>
