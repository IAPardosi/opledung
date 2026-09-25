<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kesaksian Keluarga<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 900px">
    <p class="eyebrow mb-1">Validasi bersama</p>
    <div class="judul-bagian"><h1 class="h2">Kesaksian Keluarga</h1></div>
    <p class="text-teks-2">Berikut pendaftaran dan usulan dari kerabat dekat Anda (satu pomparan). Nyatakan <b>benar</b> hanya bila Anda mengenal orang tersebut
        dan silsilahnya sesuai. Kesaksian Anda membantu Admin Wilayah memutuskan.</p>

    <?php if ($rows === []) : ?>
        <div class="card card-body text-center py-5 text-teks-2"><i class="bi bi-inbox fs-2"></i><p class="mb-0 mt-2">Tidak ada yang menunggu kesaksian Anda.</p></div>
    <?php endif ?>

    <?php foreach ($rows as $r) : ?>
        <div class="card mb-3 kartu-aksen">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <div class="kategori text-utama"><?= esc(UsulanService::JENIS[$r['jenis']] ?? $r['jenis']) ?> · <?= esc(tanggal_indo($r['created_at'])) ?></div>
                        <?php if ($r['jenis'] === 'klaim_profil') : ?>
                            <h2 class="h5 mb-1">Akun <b><?= esc($r['username']) ?></b> mengaku sebagai <?= esc($r['diklaim']?->nama_lengkap ?? '') ?></h2>
                            <div class="small text-teks-2"><?= esc($r['diklaim']?->kode_anggota ?? '') ?> · Sundut <?= (int) ($r['diklaim']?->generasi_ke ?? 0) ?></div>
                        <?php else : ?>
                            <h2 class="h5 mb-1"><?= esc($r['data']['nama_lengkap'] ?? '') ?></h2>
                            <div class="small text-teks-2">
                                <?= $r['jenis'] === 'tambah_anak' ? 'Anak dari' : 'Keturunan' ?> <b><?= esc($r['nama_leluhur']) ?></b> (Sundut <?= (int) $r['generasi_leluhur'] ?>)
                                <?php if (! empty($r['payload']['antara'])) : ?> melalui <?= esc(implode(' → ', array_column($r['payload']['antara'], 'nama_lengkap'))) ?><?php endif ?>
                                · akan tercatat di Sundut <?= $r['generasi'] ?>
                            </div>
                            <?php if (! empty($r['data']['nama_ibu'])) : ?><div class="small text-teks-2">Ibu: <?= esc($r['data']['nama_ibu']) ?></div><?php endif ?>
                        <?php endif ?>
                        <?php if ($r['catatan_pengusul']) : ?><div class="small mt-1"><i class="bi bi-chat-left-text"></i> <?= esc($r['catatan_pengusul']) ?></div><?php endif ?>
                    </div>
                    <div class="text-end small text-teks-2">
                        <?= $r['kesaksian']['benar'] ?> membenarkan<?= $r['kesaksian']['salah'] ? ' · ' . $r['kesaksian']['salah'] . ' menyangkal' : '' ?>
                        <?php if ($r['kesaksian_saya'] !== null) : ?><div class="mt-1"><span class="badge <?= (int) $r['kesaksian_saya'] === 1 ? 'text-bg-success' : 'text-bg-danger' ?>">Anda: <?= (int) $r['kesaksian_saya'] === 1 ? 'benar' : 'tidak benar' ?></span></div><?php endif ?>
                    </div>
                </div>
                <form method="post" action="<?= site_url('konfirmasi-keluarga/' . $r['id']) ?>" class="row g-2 mt-2">
                    <?= csrf_field() ?>
                    <div class="col-md"><input class="form-control form-control-sm" name="catatan" maxlength="500" placeholder="Catatan (mis. dia anak dari abang saya)"></div>
                    <div class="col-auto"><button name="benar" value="1" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Benar</button></div>
                    <div class="col-auto"><button name="benar" value="0" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Tidak benar</button></div>
                </form>
            </div>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
