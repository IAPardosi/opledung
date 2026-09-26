<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Validasi Keluarga<?= $this->endSection() ?>

<?= $this->section('main') ?>
<?php
$ringkas = static function (array $r): string {
    if ($r['jenis'] === 'klaim_profil') {
        return '<b>@' . esc($r['username']) . '</b> mengaku sebagai <b>' . esc($r['acuan']?->nama_lengkap ?? '') . '</b> (Sundut ' . (int) ($r['acuan']?->generasi_ke ?? 0) . ')';
    }
    if ($r['jenis'] === 'daftar_anggota') {
        $jalur = implode(' → ', array_column($r['payload']['antara'] ?? [], 'nama_lengkap'));
        $kel   = array_filter([
            ! empty($r['payload']['istri']) ? 'istri ' . $r['payload']['istri']['nama_lengkap'] : null,
            ! empty($r['payload']['anak']) ? count($r['payload']['anak']) . ' anak' : null,
        ]);

        return '<b>' . esc($r['data']['nama_lengkap'] ?? '') . '</b>, keturunan ' . esc($r['acuan']?->nama_lengkap ?? '') . ($jalur ? ' melalui ' . esc($jalur) : '')
            . ', Sundut ' . $r['generasi'] . ($kel ? ' · ' . esc(implode(', ', $kel)) : '');
    }

    return esc(UsulanService::JENIS[$r['jenis']] ?? $r['jenis']) . ' untuk <b>' . esc($r['acuan']?->nama_lengkap ?? '') . '</b>' . (! empty($r['data']['nama_lengkap']) ? ': ' . esc($r['data']['nama_lengkap']) : '');
};
?>
<div class="container" style="max-width: 900px">
    <p class="eyebrow mb-1">Lapis 1 · Validasi keluarga</p>
    <h1 class="h2 mb-2">Validasi Keluarga</h1>
    <p class="text-teks-2 mb-4" style="max-width: 44rem">Anda ditunjuk karena berada dalam garis langsung (orang tua/ompung atau anak/pahompu). Nyatakan <b>benar</b> hanya bila Anda yakin data dan silsilahnya sesuai.</p>

    <?php if ($tugas === []) : ?>
        <div class="card card-body text-center py-5 text-teks-2 mb-4"><i class="bi bi-inbox fs-2"></i><p class="mb-0 mt-2">Tidak ada yang menunggu validasi Anda.</p></div>
    <?php endif ?>

    <?php foreach ($tugas as $r) : ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="kategori text-utama mb-1"><?= esc(UsulanService::JENIS[$r['jenis']] ?? $r['jenis']) ?> · <?= esc(tanggal_indo($r['created_at'])) ?> · dari @<?= esc($r['username']) ?></div>
                <div class="mb-2"><?= $ringkas($r) ?></div>
                <?php if ($r['catatan_pengusul']) : ?><div class="small text-teks-2 mb-2"><i class="bi bi-chat-left-text"></i> <?= esc($r['catatan_pengusul']) ?></div><?php endif ?>
                <form method="post" action="<?= site_url('konfirmasi-keluarga/' . $r['id'] . '/validasi') ?>" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-md"><input class="form-control form-control-sm" name="catatan" maxlength="1000" placeholder="Catatan (wajib bila tidak benar)" aria-label="Catatan"></div>
                    <div class="col-auto"><button name="benar" value="1" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Benar</button></div>
                    <div class="col-auto"><button name="benar" value="0" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Tidak benar</button></div>
                </form>
            </div>
        </div>
    <?php endforeach ?>

    <?php if ($kesaksian !== []) : ?>
        <h2 class="h5 mt-5 mb-2">Kesaksian kerabat (opsional)</h2>
        <p class="small text-teks-2">Pendaftaran di pomparan dekat Anda. Kesaksian membantu penatua, tetapi tidak wajib.</p>
        <?php foreach ($kesaksian as $r) : ?>
            <div class="card mb-2">
                <div class="card-body py-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div class="small"><?= $ringkas($r) ?>
                        <?php if ($r['kesaksian_saya'] !== null) : ?> <span class="chip <?= (int) $r['kesaksian_saya'] === 1 ? 'chip-hijau' : 'chip-merah' ?>">Anda: <?= (int) $r['kesaksian_saya'] === 1 ? 'benar' : 'tidak benar' ?></span><?php endif ?>
                    </div>
                    <form method="post" action="<?= site_url('konfirmasi-keluarga/' . $r['id']) ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <button name="benar" value="1" class="btn btn-sm btn-outline-secondary">Benar</button>
                        <button name="benar" value="0" class="btn btn-sm btn-outline-danger">Tidak benar</button>
                    </form>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
