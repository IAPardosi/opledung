<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Status Pendaftaran<?= $this->endSection() ?>

<?= $this->section('main') ?>
<?php
$antara = $usulan['payload']['antara'] ?? [];
$istri  = $usulan['payload']['istri'] ?? null;
$anak   = $usulan['payload']['anak'] ?? [];
?>
<div class="container" style="max-width: 880px">
    <p class="eyebrow mb-1"><?= esc(UsulanService::JENIS[$usulan['jenis']]) ?> · diajukan <?= esc(tanggal_indo($usulan['created_at'])) ?></p>
    <h1 class="h2 mb-3">Pendaftaran Anda sedang diproses</h1>
    <div class="mb-4"><?= view('partials/tahap_validasi', ['usulan' => $usulan]) ?></div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card card-body h-100">
                <div class="fw-bold mb-3"><?= $usulan['jenis'] === 'klaim_profil' ? 'Data yang Anda klaim' : 'Garis keturunan yang diajukan' ?></div>
                <div class="linimasa">
                    <?php if ($acuan) : ?>
                        <div class="simpul"><span class="nomor"><?= $acuan->generasi_ke ?></span><div class="isi"><div class="nama"><?= esc($acuan->nama_lengkap) ?></div><div class="ket"><?= $usulan['jenis'] === 'klaim_profil' ? 'Data yang diklaim' : 'Sudah tercatat' ?></div></div></div>
                    <?php endif ?>
                    <?php foreach ($antara as $i => $a) : ?>
                        <div class="sambung"></div>
                        <div class="simpul"><span class="nomor lipat"><?= $acuan->generasi_ke + $i + 1 ?></span><div class="isi"><div class="nama"><?= esc($a['nama_lengkap']) ?></div><div class="ket">Baru</div></div></div>
                    <?php endforeach ?>
                    <?php if ($usulan['jenis'] === 'daftar_anggota') : ?>
                        <div class="sambung merah"></div>
                        <div class="simpul"><span class="nomor saya"><?= $acuan->generasi_ke + count($antara) + 1 ?></span><div class="isi"><div class="nama"><?= esc($data['nama_lengkap'] ?? '') ?></div><div class="ket">Anda<?= $istri ? ' · ' . esc($istri['nama_lengkap']) : '' ?><?= $anak ? ' · ' . count($anak) . ' anak' : '' ?></div></div></div>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <div class="col-md-5 d-flex flex-column gap-3">
            <div class="card card-body">
                <div class="fw-bold mb-1"><i class="bi bi-people text-utama"></i> Validator keluarga</div>
                <?php if ($validator) : ?>
                    <p class="small mb-1"><b>@<?= esc($validator->username) ?></b> diminta memastikan data Anda.</p>
                    <?php if (($usulan['status_keluarga'] ?? null) === 'menunggu') : ?><p class="small text-teks-2 mb-0">Hubungi beliau agar membuka menu <b>Validasi keluarga</b>.</p><?php endif ?>
                    <?php if ($usulan['catatan_keluarga']) : ?><p class="small text-teks-2 mb-0">"<?= esc($usulan['catatan_keluarga']) ?>"</p><?php endif ?>
                <?php else : ?>
                    <p class="small text-teks-2 mb-0">Belum ada keluarga garis langsung yang menjadi member, jadi penatua punguan akan memeriksa lebih teliti.</p>
                <?php endif ?>
            </div>
            <div class="card card-body">
                <div class="fw-bold mb-1"><i class="bi bi-patch-check text-utama"></i> Pengesahan punguan</div>
                <p class="small text-teks-2 mb-0">Setelah keluarga membenarkan, penatua punguan mengesahkan. Akun Anda lalu menjadi <b>member</b>.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
