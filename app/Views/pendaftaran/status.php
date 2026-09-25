<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Status Pendaftaran<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 820px">
    <p class="eyebrow mb-1">Pendaftaran member</p>
    <div class="judul-bagian"><h1 class="h2">Menunggu Validasi</h1></div>

    <div class="tutur mb-4"><div class="ipon-kecil"></div><div class="isi">
        <div class="label"><?= esc(UsulanService::JENIS[$usulan['jenis']]) ?> · diajukan <?= esc(tanggal_indo($usulan['created_at'])) ?></div>
        <div class="sebutan" style="font-size:1.6rem"><?= esc($usulan['jenis'] === 'klaim_profil' ? $leluhur?->nama_lengkap : ($data['nama_lengkap'] ?? '')) ?></div>
        <?php if ($usulan['jenis'] === 'daftar_anggota' && $leluhur) : ?>
            <div class="ket">Sundut <?= $leluhur->generasi_ke + count($usulan['payload']['antara'] ?? []) + 1 ?> · keturunan <?= esc($leluhur->nama_lengkap) ?> (Sundut <?= $leluhur->generasi_ke ?>)</div>
            <div class="rantai mt-3">
                <span class="simpul temu"><?= esc($leluhur->nama_lengkap) ?><small>Sundut <?= $leluhur->generasi_ke ?> · tercatat</small></span>
                <?php foreach ($usulan['payload']['antara'] ?? [] as $i => $a) : ?>
                    <i class="bi bi-chevron-right" style="color:#8f8079"></i><span class="simpul"><?= esc($a['nama_lengkap']) ?><small>Sundut <?= $leluhur->generasi_ke + $i + 1 ?> · baru</small></span>
                <?php endforeach ?>
                <i class="bi bi-chevron-right" style="color:#8f8079"></i><span class="simpul ujung"><?= esc($data['nama_lengkap'] ?? '') ?><small>Anda</small></span>
            </div>
        <?php endif ?>
    </div></div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card card-body h-100">
                <div class="fw-semibold mb-1"><i class="bi bi-people text-utama"></i> Kesaksian keluarga</div>
                <div class="display-6 judul"><?= $kesaksian['benar'] ?> <span class="fs-6 text-teks-2">membenarkan</span></div>
                <?php if ($kesaksian['salah'] > 0) : ?><div class="small text-danger"><?= $kesaksian['salah'] ?> kerabat menyatakan tidak benar.</div><?php endif ?>
                <p class="small text-teks-2 mt-2 mb-0">Minta kerabat dekat Anda (ayah, saudara, tulang, atau ompung) yang sudah menjadi member untuk membuka menu <b>Kesaksian Keluarga</b> dan membenarkan data Anda.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-body h-100">
                <div class="fw-semibold mb-1"><i class="bi bi-patch-check text-utama"></i> Validasi admin</div>
                <p class="small text-teks-2 mb-0">Admin Wilayah untuk domisili atau cabang pomparan Anda akan memeriksa dan memutuskan. Setelah disetujui, akun Anda menjadi <b>member</b>
                    dan dapat melihat profil serta partuturan keluarga.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
