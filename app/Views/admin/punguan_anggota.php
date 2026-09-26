<?php
use App\Models\KeanggotaanPunguanModel;

$chipStatus = [
    'menunggu' => '<span class="chip"><i class="bi bi-hourglass-split"></i> Menunggu</span>',
    'aktif'    => '<span class="chip chip-merah"><i class="bi bi-people-fill"></i> Aktif</span>',
    'nonaktif' => '<span class="chip">Nonaktif</span>',
    'ditolak'  => '<span class="chip">Ditolak</span>',
];
$q = static fn (array $ubah = []): string => site_url('admin/punguan-anggota') . '?' . http_build_query(array_filter([
    'punguan' => $punguan['id'] ?? null, 'status' => $status, 'q' => $cari, ...$ubah,
], static fn ($v) => $v !== null && $v !== ''));
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Member Punguan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="judul-bagian flex-wrap">
        <div>
            <p class="eyebrow mb-1">Keanggotaan</p>
            <h1 class="h3">Member Punguan<?= $punguan ? ' · ' . esc($punguan['nama']) : '' ?></h1>
        </div>
        <?php if (count($daftar) > 1) : ?>
            <form method="get" class="d-flex gap-2">
                <select name="punguan" class="form-select" onchange="this.form.submit()" aria-label="Pilih punguan">
                    <?php foreach ($daftar as $d) : ?>
                        <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) ($punguan['id'] ?? 0) ? 'selected' : '' ?>><?= esc($d['nama']) ?></option>
                    <?php endforeach ?>
                </select>
            </form>
        <?php endif ?>
    </div>

    <div class="card card-body mb-4 bg-transparent border-0 p-0">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-body h-100">
                    <div class="d-flex gap-3 align-items-start">
                        <span class="chip"><i class="bi bi-diagram-3"></i> Member Marga</span>
                        <p class="small text-teks-2 mb-0">Tercatat di silsilah marga, di mana pun tinggalnya. <b>Tidak</b> memiliki kewajiban punguan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-body h-100">
                    <div class="d-flex gap-3 align-items-start">
                        <span class="chip chip-merah text-nowrap"><i class="bi bi-people-fill"></i> Member Punguan</span>
                        <p class="small text-teks-2 mb-0">Member marga yang resmi terdaftar dan <b>disahkan Penatua</b> di punguan ini. Memiliki kewajiban (iuran, dll.) dan catatan keuangan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($punguan === null) : ?>
        <div class="card card-body text-center py-5 text-teks-2">
            <i class="bi bi-geo-alt fs-1"></i>
            <p class="mt-2 mb-0">Akun Anda belum terhubung ke punguan mana pun. Minta Super Admin mengatur punguan atau lingkup Anda di menu Pengguna.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            <div class="col-lg-8 order-2 order-lg-1">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link<?= $status === null ? ' active' : '' ?>" href="<?= $q(['status' => null]) ?>">Semua</a></li>
                        <?php foreach (KeanggotaanPunguanModel::STATUS as $k => $v) : ?>
                            <li class="nav-item"><a class="nav-link<?= $status === $k ? ' active' : '' ?>" href="<?= $q(['status' => $k]) ?>"><?= $k === 'menunggu' ? 'Menunggu' : $v ?> <span class="badge <?= $k === 'menunggu' && $rekap[$k] > 0 ? 'text-bg-danger' : 'text-bg-light' ?>"><?= $rekap[$k] ?></span></a></li>
                        <?php endforeach ?>
                    </ul>
                    <form method="get" class="d-flex gap-2">
                        <input type="hidden" name="punguan" value="<?= $punguan['id'] ?>">
                        <?php if ($status) : ?><input type="hidden" name="status" value="<?= esc($status) ?>"><?php endif ?>
                        <input type="search" name="q" value="<?= esc($cari) ?>" class="form-control form-control-sm input-kapsul" placeholder="Cari nama / nomor…" aria-label="Cari">
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Anggota</th><th>No. anggota</th><th>Akun web</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                            <?php if ($rows === []) : ?>
                                <tr><td colspan="5" class="text-center text-teks-2 py-4">Belum ada data.</td></tr>
                            <?php endif ?>
                            <?php foreach ($rows as $r) : ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('anggota/' . $r['person_id']) ?>" class="fw-semibold text-body"><?= esc($r['nama_lengkap']) ?></a><?= $r['status_hidup'] === 'meninggal' ? ' <span class="text-teks-3">†</span>' : '' ?>
                                        <div class="small text-teks-2">Sundut <?= (int) $r['generasi_ke'] ?> · <span class="font-monospace"><?= esc($r['kode_anggota']) ?></span></div>
                                        <?php if ($r['catatan']) : ?><div class="small text-teks-3"><i class="bi bi-chat-left-text"></i> <?= esc($r['catatan']) ?></div><?php endif ?>
                                    </td>
                                    <td class="small"><span class="font-monospace"><?= esc($r['nomor_anggota'] ?? '–') ?></span>
                                        <?php if ($r['tanggal_masuk']) : ?><div class="text-teks-3 text-nowrap">sejak <?= esc(tanggal_indo($r['tanggal_masuk'])) ?></div><?php endif ?>
                                    </td>
                                    <td class="small"><?= $r['akun'] ? '<i class="bi bi-person-check"></i> ' . esc($r['akun']) : '<span class="text-teks-3">Tanpa akun</span>' ?></td>
                                    <td><?= $chipStatus[$r['status']] ?>
                                        <?php if ($r['status'] === 'menunggu' && $r['pengaju']) : ?><div class="small text-teks-3">diajukan <?= esc($r['pengaju']) ?></div><?php endif ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($r['status'] === 'menunggu' && $sahkan) : ?>
                                            <form method="post" action="<?= site_url('admin/punguan-anggota/' . $r['id'] . '/sahkan') ?>" class="d-flex gap-1 justify-content-end flex-wrap">
                                                <?= csrf_field() ?>
                                                <input type="text" name="catatan" class="form-control form-control-sm" style="max-width:150px" placeholder="Catatan / alasan tolak" aria-label="Catatan">
                                                <button name="aksi" value="setuju" class="btn btn-sm btn-utama"><i class="bi bi-check-lg"></i> Sahkan</button>
                                                <button name="aksi" value="tolak" class="btn btn-sm btn-outline-secondary">Tolak</button>
                                            </form>
                                        <?php elseif ($r['status'] === 'menunggu') : ?>
                                            <span class="small text-teks-3">Menunggu Penatua</span>
                                        <?php elseif ($r['status'] === 'aktif') : ?>
                                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                <?php if (auth()->user()->can('keuangan.catat')) : ?>
                                                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/keuangan/anggota/' . $r['id']) ?>"><i class="bi bi-wallet2"></i> Keuangan</a>
                                                <?php endif ?>
                                                <?php if ($sahkan) : ?>
                                                    <details class="aksi-lipat">
                                                        <summary class="btn btn-sm btn-outline-secondary">Nonaktifkan</summary>
                                                        <form method="post" action="<?= site_url('admin/punguan-anggota/' . $r['id'] . '/nonaktifkan') ?>" class="card card-body p-2 mt-1 text-start">
                                                            <?= csrf_field() ?>
                                                            <input type="text" name="alasan" class="form-control form-control-sm mb-1" required placeholder="Alasan (pindah, mundur, meninggal…)">
                                                            <input type="date" name="tanggal_keluar" class="form-control form-control-sm mb-1" aria-label="Tanggal keluar">
                                                            <button class="btn btn-sm btn-gelap">Simpan</button>
                                                        </form>
                                                    </details>
                                                <?php endif ?>
                                            </div>
                                        <?php elseif ($r['status'] === 'nonaktif' && $sahkan) : ?>
                                            <form method="post" action="<?= site_url('admin/punguan-anggota/' . $r['id'] . '/aktifkan') ?>">
                                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Aktifkan kembali</button>
                                            </form>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 order-1 order-lg-2">
                <div class="card">
                    <div class="card-header fw-semibold"><i class="bi bi-person-plus"></i> Daftarkan member punguan</div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('admin/punguan-anggota/tambah') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="punguan_id" value="<?= $punguan['id'] ?>">
                            <label class="form-label small fw-semibold" for="cariOrang">Nama di silsilah</label>
                            <div class="position-relative mb-3">
                                <input type="search" id="cariOrang" class="form-control pilih-orang" data-target="person_id" autocomplete="off" placeholder="Ketik nama anggota…" required>
                                <input type="hidden" name="person_id" id="person_id">
                                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:10"></div>
                            </div>
                            <label class="form-label small fw-semibold" for="tanggal_masuk">Tanggal masuk</label>
                            <input type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control mb-3" value="<?= date('Y-m-d') ?>">
                            <label class="form-label small fw-semibold" for="catatan">Catatan</label>
                            <input type="text" name="catatan" id="catatan" class="form-control mb-3" placeholder="mis. kepala keluarga, domisili Medan Johor">
                            <button class="btn btn-utama w-100"><?= $sahkan ? 'Daftarkan & sahkan' : 'Ajukan ke Penatua' ?></button>
                        </form>
                        <p class="small text-teks-2 mt-3 mb-0">
                            <?= $sahkan ? 'Sebagai Penatua, pendaftaran Anda langsung sah dan mendapat nomor anggota.' : 'Pendaftaran oleh Humas menunggu pengesahan Penatua punguan.' ?>
                            Member juga dapat mengajukan diri dari halaman profilnya.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>window.SILSILAH_URL = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;</script>
<script src="<?= base_url('assets/js/pilih-orang.js') ?>"></script>
<?= $this->endSection() ?>
