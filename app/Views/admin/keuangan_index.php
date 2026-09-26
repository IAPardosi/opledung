<?php
use App\Models\KeuanganCatatanModel;
use App\Models\KeuanganKategoriModel;
use App\Services\KeuanganService;

$bulan   = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$sel     = [
    'sah'      => ['sel-sah', '<i class="bi bi-check-lg"></i>', 'Sah'],
    'menunggu' => ['sel-tunggu', '<i class="bi bi-hourglass-split"></i>', 'Menunggu validasi'],
    'belum'    => ['sel-belum', '·', 'Belum bayar'],
];
$chip = [
    'sah'      => '<span class="chip chip-hijau"><i class="bi bi-check-circle-fill"></i> Sah</span>',
    'menunggu' => '<span class="chip"><i class="bi bi-hourglass-split"></i> Menunggu</span>',
    'ditolak'  => '<span class="chip chip-merah"><i class="bi bi-x-circle-fill"></i> Ditolak</span>',
];
$pq = static fn (array $ubah = []): string => site_url('admin/keuangan') . '?' . http_build_query(array_filter([
    'punguan' => $punguan['id'] ?? null, 'tahun' => $tahun, ...$ubah,
], static fn ($v) => $v !== null && $v !== ''));
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Keuangan Punguan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="judul-bagian flex-wrap">
        <div>
            <p class="eyebrow mb-1">Keuangan punguan · khusus Member Punguan</p>
            <h1 class="h3"><?= $punguan ? esc($punguan['nama']) : 'Keuangan' ?> · <?= $tahun ?></h1>
        </div>
        <?php if ($punguan) : ?>
            <div class="d-flex flex-wrap gap-2">
                <form method="get" class="d-flex gap-2">
                    <?php if (count($daftar) > 1) : ?>
                        <select name="punguan" class="form-select" onchange="this.form.submit()" aria-label="Punguan">
                            <?php foreach ($daftar as $d) : ?><option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) $punguan['id'] ? 'selected' : '' ?>><?= esc($d['nama']) ?></option><?php endforeach ?>
                        </select>
                    <?php else : ?><input type="hidden" name="punguan" value="<?= $punguan['id'] ?>"><?php endif ?>
                    <select name="tahun" class="form-select" style="width:auto;min-width:6.5rem" onchange="this.form.submit()" aria-label="Tahun">
                        <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 5; $t--) : ?><option <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option><?php endfor ?>
                    </select>
                </form>
                <a class="btn btn-outline-secondary" href="<?= site_url('admin/keuangan/kategori?punguan=' . $punguan['id']) ?>"><i class="bi bi-sliders"></i> Kategori</a>
                <a class="btn btn-utama" href="<?= site_url('admin/keuangan/catat?punguan=' . $punguan['id']) ?>"><i class="bi bi-plus-lg"></i> Catat pembayaran</a>
            </div>
        <?php endif ?>
    </div>

    <?php if ($punguan === null) : ?>
        <div class="card card-body text-center py-5 text-teks-2">
            <i class="bi bi-wallet2 fs-1"></i>
            <p class="mt-2 mb-0">Akun Anda belum terhubung ke punguan mana pun.</p>
        </div>
    <?php else : ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card card-body h-100">
                    <div class="small text-teks-2">Member punguan aktif</div>
                    <div class="fs-3 judul"><?= $anggotaAktif ?></div>
                    <a class="small" href="<?= site_url('admin/punguan-anggota?punguan=' . $punguan['id']) ?>">Kelola member →</a>
                </div>
            </div>
            <?php foreach ($ringkasan as $r) : ?>
                <div class="col-6 col-lg-3">
                    <div class="card card-body h-100">
                        <div class="small text-teks-2"><?= esc($r['nama']) ?> · <?= $tahun ?></div>
                        <div class="fs-4 judul"><?= rupiah($r['sah']) ?></div>
                        <?php if ($r['jumlah_menunggu'] > 0) : ?>
                            <div class="small text-warning-emphasis"><i class="bi bi-hourglass-split"></i> <?= $r['jumlah_menunggu'] ?> menunggu · <?= rupiah($r['menunggu']) ?></div>
                        <?php else : ?><div class="small text-teks-3">Semua sudah divalidasi</div><?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
            <?php if ($ringkasan === []) : ?>
                <div class="col-lg-9"><div class="card card-body h-100 text-teks-2 small">Belum ada kategori keuangan. <a href="<?= site_url('admin/keuangan/kategori?punguan=' . $punguan['id']) ?>">Atur kategori</a> seperti Iuran Bulanan, Hamauliateon, dan Toktok Ripe.</div></div>
            <?php endif ?>
        </div>

        <?php if ($menunggu !== []) : ?>
            <div class="card mb-4 border-warning">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-hourglass-split"></i> Menunggu validasi Penatua (<?= count($menunggu) ?>)</span>
                    <?php if ($validator) : ?><button form="formSahBanyak" class="btn btn-sm btn-utama"><i class="bi bi-check2-all"></i> Sahkan yang dipilih</button><?php endif ?>
                </div>
                <?php if ($validator) : ?><form method="post" action="<?= site_url('admin/keuangan/validasi-banyak') ?>" id="formSahBanyak"><?= csrf_field() ?></form><?php endif ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><?php if ($validator) : ?><th style="width:2rem"><input type="checkbox" class="form-check-input" id="pilihSemua" aria-label="Pilih semua"></th><?php endif ?><th>Anggota</th><th>Kategori</th><th>Periode</th><th class="text-end">Nominal</th><th>Dicatat</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($menunggu as $c) : ?>
                            <tr>
                                <?php if ($validator) : ?><td><input type="checkbox" class="form-check-input pilih-sah" name="ids[]" value="<?= $c['id'] ?>" form="formSahBanyak" aria-label="Pilih"></td><?php endif ?>
                                <td class="fw-semibold"><?= esc($c['nama_lengkap']) ?></td>
                                <td><?= esc($c['kategori']) ?><?php if ($c['keterangan']) : ?><div class="small text-teks-3"><?= esc($c['keterangan']) ?></div><?php endif ?></td>
                                <td><?= esc(KeuanganService::labelPeriode($c['periode'])) ?></td>
                                <td class="text-end text-nowrap"><?= rupiah($c['nominal']) ?></td>
                                <td class="small"><?= esc($c['pencatat'] ?? '–') ?><div class="text-teks-3"><?= esc(tanggal_indo($c['tanggal'])) ?> · <?= esc(KeuanganCatatanModel::METODE[$c['metode']] ?? $c['metode']) ?></div></td>
                                <td class="text-end">
                                    <?php if ($validator) : ?>
                                        <form method="post" action="<?= site_url('admin/keuangan/' . $c['id'] . '/validasi') ?>" class="d-flex gap-1 justify-content-end flex-wrap">
                                            <?= csrf_field() ?>
                                            <input type="text" name="alasan" class="form-control form-control-sm" style="max-width:140px" placeholder="Alasan bila ditolak" aria-label="Alasan">
                                            <button name="aksi" value="sah" class="btn btn-sm btn-utama">Sah</button>
                                            <button name="aksi" value="tolak" class="btn btn-sm btn-outline-secondary">Tolak</button>
                                        </form>
                                    <?php else : ?>
                                        <form method="post" action="<?= site_url('admin/keuangan/' . $c['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus catatan ini?')">
                                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i> Batalkan</button>
                                        </form>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif ?>

        <?php if ($bulanan !== []) : ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold"><i class="bi bi-calendar3"></i> Rekap <?= esc(array_column($bulanan, 'nama', 'id')[$katGrid] ?? '') ?> <?= $tahun ?></span>
                    <div class="d-flex align-items-center gap-3 small flex-wrap">
                        <?php if (count($bulanan) > 1) : ?>
                            <span><?php foreach ($bulanan as $b) : ?><a class="me-2<?= (int) $b['id'] === $katGrid ? ' fw-bold' : '' ?>" href="<?= $pq(['kategori' => $b['id']]) ?>"><?= esc($b['nama']) ?></a><?php endforeach ?></span>
                        <?php endif ?>
                        <?php foreach ($sel as [$kelas, $ikon, $label]) : ?><span><span class="sel-iuran <?= $kelas ?>"><?= $ikon ?></span> <?= $label ?></span><?php endforeach ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 tabel-iuran">
                        <thead class="table-light"><tr><th>Member punguan</th><?php for ($m = 1; $m <= 12; $m++) : ?><th class="text-center"><?= $bulan[$m] ?></th><?php endfor ?><th class="text-end">Total sah</th><th class="text-center">Tunggakan</th></tr></thead>
                        <tbody>
                        <?php if ($grid === []) : ?><tr><td colspan="15" class="text-center text-teks-2 py-4">Belum ada member punguan aktif.</td></tr><?php endif ?>
                        <?php foreach ($grid as $g) : ?>
                            <tr>
                                <td class="text-nowrap"><a href="<?= site_url('admin/keuangan/anggota/' . $g['keanggotaan_id']) ?>" class="fw-semibold text-body"><?= esc($g['nama']) ?></a><div class="small text-teks-3 font-monospace"><?= esc($g['nomor'] ?? $g['kode']) ?></div></td>
                                <?php for ($m = 1; $m <= 12; $m++) : ?>
                                    <?php $s = $g['bulan'][$m]; ?>
                                    <td class="text-center"><?php if ($s !== null) : ?><span class="sel-iuran <?= $sel[$s][0] ?>" title="<?= $bulan[$m] . ' ' . $tahun . ': ' . $sel[$s][2] ?>"><?= $sel[$s][1] ?></span><?php endif ?></td>
                                <?php endfor ?>
                                <td class="text-end text-nowrap"><?= rupiah($g['total']) ?></td>
                                <td class="text-center"><?= $g['tunggakan'] > 0 ? '<span class="chip chip-merah">' . $g['tunggakan'] . ' bln</span>' : '<span class="text-success"><i class="bi bi-check-circle"></i></span>' ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold"><i class="bi bi-journal-text"></i> Catatan terbaru</span>
                <ul class="nav nav-pills nav-sm small">
                    <li class="nav-item"><a class="nav-link py-1<?= $statusCat === null ? ' active' : '' ?>" href="<?= $pq() ?>">Semua</a></li>
                    <?php foreach (KeuanganCatatanModel::STATUS as $k => $v) : ?><li class="nav-item"><a class="nav-link py-1<?= $statusCat === $k ? ' active' : '' ?>" href="<?= $pq(['status' => $k]) ?>"><?= $k === 'menunggu' ? 'Menunggu' : $v ?></a></li><?php endforeach ?>
                </ul>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Anggota</th><th>Kategori</th><th>Periode</th><th class="text-end">Nominal</th><th>Status</th><th>Validasi</th></tr></thead>
                    <tbody>
                    <?php if ($catatan === []) : ?><tr><td colspan="7" class="text-center text-teks-2 py-4">Belum ada catatan.</td></tr><?php endif ?>
                    <?php foreach ($catatan as $c) : ?>
                        <tr>
                            <td class="small text-nowrap"><?= esc(tanggal_indo($c['tanggal'])) ?></td>
                            <td><?= esc($c['nama_lengkap']) ?></td>
                            <td><?= esc($c['kategori']) ?></td>
                            <td class="small"><?= esc(KeuanganService::labelPeriode($c['periode'])) ?></td>
                            <td class="text-end text-nowrap"><?= rupiah($c['nominal']) ?></td>
                            <td><?= $chip[$c['status']] ?><?php if ($c['status'] === 'ditolak' && $c['alasan_tolak']) : ?><div class="small text-teks-3"><?= esc($c['alasan_tolak']) ?></div><?php endif ?></td>
                            <td class="small text-teks-2"><?= $c['validator'] ? esc($c['validator']) : '–' ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    document.getElementById('pilihSemua')?.addEventListener('change', (e) => {
        document.querySelectorAll('.pilih-sah').forEach((c) => { c.checked = e.target.checked; });
    });
</script>
<?= $this->endSection() ?>
