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

    <div class="card card-body mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <?= view('partials/tahap_validasi', ['usulan' => $usulan]) ?>
            <span class="small text-body-secondary"><?= $usulan['nama_punguan'] ? 'Punguan: <b>' . esc($usulan['nama_punguan']) . '</b>' : 'Tanpa punguan' ?></span>
        </div>
        <div class="small mt-2">
            <?php if ($usulan['validator_username']) : ?>
                Validator keluarga: <b>@<?= esc($usulan['validator_username']) ?></b><?= $usulan['keluarga_at'] ? ' · ' . esc(tanggal_waktu_indo($usulan['keluarga_at'])) : '' ?>
            <?php else : ?>
                Tidak ada keluarga garis langsung yang menjadi member. Periksa lebih teliti, mis. dengan menghubungi kerabat yang disebut pengusul.
            <?php endif ?>
            <?php if ($usulan['catatan_keluarga']) : ?><div class="text-body-secondary mt-1">"<?= esc($usulan['catatan_keluarga']) ?>"</div><?php endif ?>
        </div>
        <?php if ($usulan['status'] === 'pending' && $usulan['status_keluarga'] === 'menunggu') : ?>
            <form method="post" action="<?= site_url('admin/usulan/' . $usulan['id'] . '/lewati-keluarga') ?>" class="row g-2 mt-2">
                <?= csrf_field() ?>
                <div class="col-md"><input class="form-control form-control-sm" name="alasan" required maxlength="900" placeholder="Alasan melewati validasi keluarga (mis. validator sudah dihubungi lewat telepon)" aria-label="Alasan"></div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Lewati validasi keluarga</button></div>
            </form>
        <?php endif ?>
    </div>

    <?php if ($target) : ?>
    <div class="card card-body mb-3">
        <div class="small text-body-secondary mb-1"><?= match ($usulan['jenis']) {
            'daftar_anggota'  => 'Leluhur terdekat yang dipilih pendaftar',
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
        <?php if ($usulan['jenis'] === 'daftar_anggota') : ?>
            <?php $antara = $usulan['payload']['antara'] ?? []; ?>
            <div class="rantai mt-3">
                <span class="simpul temu"><?= esc($target->nama_lengkap) ?><small>Sundut <?= $target->generasi_ke ?> · sudah tercatat</small></span>
                <?php foreach ($antara as $i => $a) : ?>
                    <i class="bi bi-chevron-right text-body-secondary"></i>
                    <span class="simpul"><?= esc($a['nama_lengkap']) ?><small>Sundut <?= $target->generasi_ke + $i + 1 ?> · baru<?= ! empty($a['tahun_lahir']) ? ' · l. ' . (int) $a['tahun_lahir'] : '' ?></small></span>
                <?php endforeach ?>
                <i class="bi bi-chevron-right text-body-secondary"></i>
                <span class="simpul ujung"><?= esc($data['nama_lengkap'] ?? '') ?><small>Pendaftar · Sundut <?= $target->generasi_ke + count($antara) + 1 ?></small></span>
            </div>
            <?php $istri = $usulan['payload']['istri'] ?? null; $anak = $usulan['payload']['anak'] ?? []; ?>
            <?php if ($istri || $anak) : ?>
                <div class="small mt-3"><b>Keluarga:</b>
                    <?= $istri ? 'Istri/suami ' . esc($istri['nama_lengkap']) . (! empty($istri['marga_nama']) ? ' (' . esc($istri['marga_nama']) . ')' : '') : '' ?>
                    <?php if ($anak) : ?><?= $istri ? ' · ' : '' ?><?= count($anak) ?> anak: <?= esc(implode(', ', array_map(static fn ($a) => $a['nama_lengkap'] . ' (' . $a['jenis_kelamin'] . ')', $anak))) ?><?php endif ?>
                </div>
            <?php endif ?>
            <div class="small text-body-secondary mt-2">Bila disahkan: <?= count($antara) ?> generasi antara, kepala keluarga<?= $istri ? ', pasangan' : '' ?><?= $anak ? ', dan ' . count($anak) . ' anak' : '' ?> dicatat; akun ditautkan dan menjadi member.</div>
        <?php endif ?>
    </div>
    <?php endif ?>

    <div class="card card-body mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold"><i class="bi bi-people text-utama"></i> Kesaksian keluarga</div>
            <div class="small"><span class="badge text-bg-success"><?= $kesaksian['benar'] ?> benar</span> <span class="badge text-bg-danger"><?= $kesaksian['salah'] ?> tidak benar</span></div>
        </div>
        <?php if ($kesaksian['daftar'] === []) : ?>
            <div class="small text-body-secondary mt-1">Belum ada kerabat yang memberi kesaksian.</div>
        <?php endif ?>
        <?php foreach ($kesaksian['daftar'] as $k) : ?>
            <div class="small mt-2 d-flex gap-2">
                <i class="bi <?= (int) $k['benar'] === 1 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>"></i>
                <div><b><?= esc($k['nama_lengkap'] ?? $k['username']) ?></b> <span class="text-body-secondary">(<?= esc($k['kode_anggota'] ?? '') ?>, Sundut <?= (int) $k['generasi_ke'] ?>)</span>
                    <?php if ($k['catatan']) : ?><div class="text-body-secondary">"<?= esc($k['catatan']) ?>"</div><?php endif ?></div>
            </div>
        <?php endforeach ?>
    </div>

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
                <button class="btn btn-success mt-auto" <?= in_array($usulan['status_keluarga'], ['menunggu', 'salah'], true) ? 'disabled' : '' ?>><i class="bi bi-check-lg"></i> Sahkan & masukkan ke silsilah</button>
                <?php if ($usulan['status_keluarga'] === 'menunggu') : ?><div class="small text-body-secondary mt-2">Menunggu validasi keluarga terlebih dahulu.</div><?php endif ?>
                <?php if ($usulan['status_keluarga'] === 'salah') : ?><div class="small text-danger mt-2">Keluarga menyatakan tidak benar; tolak agar pengusul memperbaiki.</div><?php endif ?>
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
