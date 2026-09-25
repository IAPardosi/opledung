<?php
use App\Services\UsulanService;

$cfg  = config('Silsilah');
$lama = $target?->toRawArray() ?? [];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Periksa Usulan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 900px">
    <a href="<?= site_url('admin/usulan') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar usulan</a>
    <div class="d-flex justify-content-between align-items-start mt-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><?= esc(UsulanService::JENIS[$usulan['jenis']] ?? $usulan['jenis']) ?></h1>
            <div class="text-body-secondary small">Diajukan oleh <b><?= esc($usulan['username']) ?></b> pada <?= esc(tanggal_indo($usulan['created_at'])) ?></div>
        </div>
        <?= view('partials/status_usulan', ['status' => $usulan['status']]) ?>
    </div>

    <?php if ($target) : ?>
    <div class="card card-body mb-3">
        <div class="small text-body-secondary mb-1"><?= match ($usulan['jenis']) {
            'tambah_anak'     => 'Anak dari',
            'tambah_pasangan' => 'Pasangan untuk',
            'klaim_profil'    => 'Akun ingin ditautkan ke',
            default           => 'Data yang diubah',
        } ?></div>
        <div><a href="<?= site_url('anggota/' . $target->id) ?>" class="fw-semibold"><?= esc($target->nama_lengkap) ?></a>
            <span class="text-body-secondary small">· <?= esc($target->kode_anggota) ?> · Generasi <?= $target->generasi_ke ?></span> <?= badge_garis($target->garis) ?></div>
        <?php if ($usulan['jenis'] === 'tambah_anak') : ?>
            <div class="small mt-1">Anak akan tercatat di <b>Generasi <?= $target->generasi_ke + 1 ?></b>.</div>
        <?php endif ?>
    </div>
    <?php endif ?>

    <?php if ($usulan['catatan_pengusul']) : ?>
        <div class="alert alert-light border"><b>Catatan pengusul:</b> <?= nl2br(esc($usulan['catatan_pengusul'])) ?></div>
    <?php endif ?>

    <?php if ($data !== []) : ?>
    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light"><tr><th style="width:30%">Kolom</th><?php if ($usulan['jenis'] === 'ubah_data') : ?><th>Data sekarang</th><?php endif ?><th>Usulan</th></tr></thead>
                <tbody>
                <?php foreach ($data as $k => $v) : ?>
                    <?php if ($v === null || $v === '' || ($k === 'sembunyikan_kontak' && ! $v && $usulan['jenis'] !== 'ubah_data')) : continue; endif ?>
                    <tr>
                        <td class="small text-body-secondary"><?= esc($cfg->labelKolom[$k] ?? $k) ?></td>
                        <?php if ($usulan['jenis'] === 'ubah_data') : ?>
                            <td class="small"><?= in_array($k, ['nik', 'no_kk'], true) ? '<i>(tersimpan terenkripsi)</i>' : esc((string) ($lama[$k] ?? '')) ?></td>
                        <?php endif ?>
                        <td class="fw-medium"><?= esc(is_scalar($v) ? (string) $v : json_encode($v)) ?></td>
                    </tr>
                <?php endforeach ?>
                <?php foreach (($usulan['payload']['pernikahan'] ?? []) as $k => $v) : ?>
                    <?php if ($v) : ?><tr><td class="small text-body-secondary"><?= esc(ucfirst(str_replace('_', ' ', $k))) ?></td><td class="fw-medium"><?= esc($v) ?></td></tr><?php endif ?>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <?php if ($usulan['status'] === 'pending') : ?>
    <div class="row g-3">
        <div class="col-md-6">
            <form method="post" action="<?= site_url('admin/usulan/' . $usulan['id'] . '/setujui') ?>" class="card card-body h-100">
                <?= csrf_field() ?>
                <label class="form-label small" for="catatanSetuju">Catatan (opsional)</label>
                <textarea class="form-control mb-2" id="catatanSetuju" name="catatan" rows="2"></textarea>
                <button class="btn btn-success mt-auto"><i class="bi bi-check-lg"></i> Setujui & masukkan ke silsilah</button>
            </form>
        </div>
        <div class="col-md-6">
            <form method="post" action="<?= site_url('admin/usulan/' . $usulan['id'] . '/tolak') ?>" class="card card-body h-100">
                <?= csrf_field() ?>
                <label class="form-label small" for="catatanTolak">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea class="form-control mb-2" id="catatanTolak" name="catatan" rows="2" required></textarea>
                <button class="btn btn-outline-danger mt-auto"><i class="bi bi-x-lg"></i> Tolak</button>
            </form>
        </div>
    </div>
    <?php else : ?>
        <div class="card card-body">
            <div class="small text-body-secondary">Diproses pada <?= esc(tanggal_indo($usulan['reviewed_at'])) ?></div>
            <?php if ($usulan['catatan_verifikator']) : ?><div class="mt-1"><?= nl2br(esc($usulan['catatan_verifikator'])) ?></div><?php endif ?>
            <?php if ($hasil) : ?><div class="mt-2"><a href="<?= site_url('anggota/' . $hasil->id) ?>">Lihat data <?= esc($hasil->nama_lengkap) ?></a></div><?php endif ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
