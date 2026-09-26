<?php
$statusHidup = [
    'hidup'           => '<span class="chip chip-hijau">Hidup</span>',
    'meninggal'       => '<span class="chip chip-gelap">† Meninggal</span>',
    'tidak_diketahui' => '<span class="chip">Tidak diketahui</span>',
];
$jumlahMember = array_column($member, 'n', 'status');
$url          = static fn (array $ubah = []): string => site_url('admin/anggota') . '?' . http_build_query(array_filter([...$f, ...$ubah], static fn ($v) => $v !== null && $v !== ''));
$halTerakhir  = max(1, (int) ceil($total / 50));
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Data Anggota<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid px-lg-4" style="max-width: 1400px">
    <div class="judul-bagian flex-wrap">
        <div>
            <p class="eyebrow mb-1">Kontrol anggota</p>
            <h1 class="h3">Data Anggota</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="chip<?= $f['hidup'] === 'hidup' ? ' chip-gelap' : '' ?>" href="<?= $url(['hidup' => $f['hidup'] === 'hidup' ? null : 'hidup', 'hal' => null]) ?>">Hidup · <?= number_format($rekap['hidup'] ?? 0, 0, ',', '.') ?></a>
            <a class="chip<?= $f['hidup'] === 'meninggal' ? ' chip-gelap' : '' ?>" href="<?= $url(['hidup' => $f['hidup'] === 'meninggal' ? null : 'meninggal', 'hal' => null]) ?>">Meninggal · <?= number_format($rekap['meninggal'] ?? 0, 0, ',', '.') ?></a>
            <a class="chip<?= $f['hidup'] === 'tidak_diketahui' ? ' chip-gelap' : '' ?>" href="<?= $url(['hidup' => $f['hidup'] === 'tidak_diketahui' ? null : 'tidak_diketahui', 'hal' => null]) ?>">Tidak diketahui · <?= number_format($rekap['tidak_diketahui'] ?? 0, 0, ',', '.') ?></a>
            <a class="chip chip-merah" href="<?= $url(['member' => 'punguan', 'hal' => null]) ?>"><i class="bi bi-people-fill"></i> Member Punguan · <?= (int) ($jumlahMember['aktif'] ?? 0) ?></a>
        </div>
    </div>

    <form method="get" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small fw-semibold" for="q">Cari</label><input type="search" name="q" id="q" value="<?= esc($f['q']) ?>" class="form-control" placeholder="Nama, kode, atau akun"></div>
            <div class="col-6 col-md-1"><label class="form-label small fw-semibold" for="g">Sundut</label>
                <select name="g" id="g" class="form-select"><option value="">Semua</option><?php foreach ($generasi as $g) : ?><option <?= $f['g'] === (int) $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small fw-semibold" for="hidup">Status hidup</label>
                <select name="hidup" id="hidup" class="form-select"><option value="">Semua</option><?php foreach (['hidup' => 'Hidup', 'meninggal' => 'Meninggal', 'tidak_diketahui' => 'Tidak diketahui'] as $k => $v) : ?><option value="<?= $k ?>" <?= $f['hidup'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small fw-semibold" for="member">Jenis member</label>
                <select name="member" id="member" class="form-select"><option value="">Semua</option><?php foreach (['punguan' => 'Member Punguan', 'menunggu' => 'Ajuan punguan', 'marga' => 'Member Marga saja'] as $k => $v) : ?><option value="<?= $k ?>" <?= $f['member'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small fw-semibold" for="punguan">Punguan</label>
                <select name="punguan" id="punguan" class="form-select"><option value="">Semua</option><?php foreach ($punguan as $p) : ?><option value="<?= $p['id'] ?>" <?= $f['punguan'] === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?></option><?php endforeach ?></select></div>
            <div class="col-6 col-md-1"><label class="form-label small fw-semibold" for="akun">Akun web</label>
                <select name="akun" id="akun" class="form-select"><option value="">Semua</option><option value="ada" <?= $f['akun'] === 'ada' ? 'selected' : '' ?>>Ada</option><option value="tidak" <?= $f['akun'] === 'tidak' ? 'selected' : '' ?>>Tidak</option></select></div>
            <div class="col-6 col-md-1 d-grid"><button class="btn btn-gelap">Terapkan</button></div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2 small text-teks-2">
        <span><?= number_format($total, 0, ',', '.') ?> anggota<?= array_filter($f) ? ' sesuai filter · <a href="' . site_url('admin/anggota') . '">hapus filter</a>' : '' ?></span>
        <span>Halaman <?= $halaman ?> dari <?= $halTerakhir ?></span>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Anggota</th><th class="text-center">Sundut</th><th>Jenis member</th><th>Akun web</th><th>Status hidup</th><th class="text-end">Ubah status</th></tr></thead>
                <tbody>
                <?php if ($rows === []) : ?><tr><td colspan="6" class="text-center text-teks-2 py-4">Tidak ada anggota sesuai filter.</td></tr><?php endif ?>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-body" href="<?= site_url('anggota/' . $r['id']) ?>"><?= esc($r['nama_lengkap']) ?></a>
                            <div class="small text-teks-2"><span class="font-monospace"><?= esc($r['kode_anggota']) ?></span> · <?= esc(label_garis($r['garis'])) ?></div>
                        </td>
                        <td class="text-center"><?= (int) $r['generasi_ke'] ?></td>
                        <td><?= badge_member($r['status_punguan'] ? ['status' => $r['status_punguan'], 'punguan' => $r['nama_punguan']] : null) ?></td>
                        <td class="small"><?php if ($r['user_id']) : ?><i class="bi bi-person-check"></i> <?= esc($r['username']) ?><?= $r['akun_aktif'] ? '' : ' <span class="chip">nonaktif</span>' ?><?php else : ?><span class="text-teks-3">–</span><?php endif ?></td>
                        <td><?= $statusHidup[$r['status_hidup']] ?? esc($r['status_hidup']) ?>
                            <?php if ($r['status_hidup'] === 'meninggal' && ($r['tanggal_wafat'] || $r['tahun_wafat'])) : ?><div class="small text-teks-3"><?= esc($r['tanggal_wafat'] ? tanggal_indo($r['tanggal_wafat']) : (string) $r['tahun_wafat']) ?></div><?php endif ?>
                        </td>
                        <td class="text-end">
                            <?php if ($r['status_data'] === 'terkunci' && ! auth()->user()->can('silsilah.pokok')) : ?>
                                <span class="small text-teks-3" title="Silsilah Pokok hanya diubah Ketua Adat"><i class="bi bi-lock"></i> Terkunci</span>
                            <?php else : ?>
                                <details class="aksi-lipat">
                                    <summary class="btn btn-sm btn-outline-secondary">Ubah</summary>
                                    <form method="post" action="<?= site_url('admin/anggota/' . $r['id'] . '/status') ?>" class="card card-body p-2 mt-1 text-start">
                                        <?= csrf_field() ?>
                                        <select name="status_hidup" class="form-select form-select-sm mb-1 pilih-status" aria-label="Status hidup">
                                            <?php foreach (['hidup' => 'Hidup', 'meninggal' => 'Meninggal', 'tidak_diketahui' => 'Tidak diketahui'] as $k => $v) : ?><option value="<?= $k ?>" <?= $r['status_hidup'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?>
                                        </select>
                                        <div class="isian-wafat">
                                            <input type="date" name="tanggal_wafat" class="form-control form-control-sm mb-1" value="<?= esc((string) $r['tanggal_wafat']) ?>" max="<?= date('Y-m-d') ?>" aria-label="Tanggal wafat" title="Tanggal wafat">
                                            <input type="number" name="tahun_wafat" class="form-control form-control-sm mb-1" value="<?= esc((string) $r['tahun_wafat']) ?>" placeholder="atau tahun wafat" min="1500" max="<?= date('Y') ?>" aria-label="Tahun wafat">
                                            <input type="text" name="tempat_makam" class="form-control form-control-sm mb-1" value="<?= esc((string) $r['tempat_makam']) ?>" placeholder="Tempat makam (opsional)" aria-label="Tempat makam">
                                        </div>
                                        <button class="btn btn-sm btn-gelap">Simpan</button>
                                    </form>
                                </details>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($halTerakhir > 1) : ?>
        <nav class="d-flex justify-content-center gap-2 mt-3" aria-label="Halaman">
            <?php if ($halaman > 1) : ?><a class="btn btn-sm btn-outline-secondary" href="<?= $url(['hal' => $halaman - 1]) ?>">‹ Sebelumnya</a><?php endif ?>
            <?php if ($halaman < $halTerakhir) : ?><a class="btn btn-sm btn-outline-secondary" href="<?= $url(['hal' => $halaman + 1]) ?>">Berikutnya ›</a><?php endif ?>
        </nav>
    <?php endif ?>
    <p class="small text-teks-2 mt-3">Menandai anggota <b>meninggal</b> otomatis mengakhiri keanggotaan punguannya; riwayat keuangan tetap tersimpan. Data Silsilah Pokok (terkunci) hanya dapat diubah Ketua Adat. Akun web diaktifkan/nonaktifkan oleh Super Admin di menu Pengguna.</p>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    document.querySelectorAll('.pilih-status').forEach((s) => {
        const atur = () => s.form.querySelector('.isian-wafat').classList.toggle('d-none', s.value !== 'meninggal');
        s.addEventListener('change', atur);
        atur();
    });
</script>
<?= $this->endSection() ?>
