<?php
use App\Models\KeuanganKategoriModel;

$nilai = static fn (string $f, $d = '') => old($f) ?? ($k[$f] ?? $d);
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kategori Keuangan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <a href="<?= site_url('admin/keuangan?punguan=' . $punguan['id']) ?>" class="small"><i class="bi bi-arrow-left"></i> Keuangan <?= esc($punguan['nama']) ?></a>
    <h1 class="h3 mt-2 mb-3">Kategori keuangan · <?= esc($punguan['nama']) ?></h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Kategori</th><th>Jenis</th><th class="text-end">Nominal standar</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if ($kategori === []) : ?><tr><td colspan="5" class="text-center text-teks-2 py-4">Belum ada kategori.</td></tr><?php endif ?>
                        <?php foreach ($kategori as $r) : ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($r['nama']) ?><?php if ($r['keterangan']) : ?><div class="small text-teks-3 fw-normal"><?= esc($r['keterangan']) ?></div><?php endif ?></td>
                                <td class="small"><?= esc(KeuanganKategoriModel::JENIS[$r['jenis']]) ?></td>
                                <td class="text-end text-nowrap"><?= $r['nominal_standar'] !== null ? rupiah($r['nominal_standar']) : '<span class="text-teks-3">Bebas</span>' ?></td>
                                <td><?= $r['is_active'] ? '<span class="chip chip-hijau">Aktif</span>' : '<span class="chip">Nonaktif</span>' ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/keuangan/kategori?punguan=' . $punguan['id'] . '&ubah=' . $r['id']) ?>">Ubah</a></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="small text-teks-2 mt-3">Contoh: <b>Iuran Bulanan</b> (bulanan, Rp25.000), <b>Hamauliateon</b> (per peristiwa, nominal bebas), <b>Toktok Ripe</b> (per peristiwa). Kategori yang sudah dipakai tidak dihapus, cukup dinonaktifkan.</p>
        </div>
        <div class="col-lg-5">
            <form method="post" class="card card-body p-4">
                <?= csrf_field() ?>
                <h2 class="h5"><?= $k ? 'Ubah kategori' : 'Tambah kategori' ?></h2>
                <?php if ($k) : ?><input type="hidden" name="id" value="<?= $k['id'] ?>"><?php endif ?>
                <label class="form-label small fw-semibold" for="nama">Nama</label>
                <input type="text" name="nama" id="nama" class="form-control mb-3" required maxlength="80" value="<?= esc($nilai('nama')) ?>" placeholder="mis. Iuran Bulanan">
                <label class="form-label small fw-semibold" for="jenis">Jenis</label>
                <select name="jenis" id="jenis" class="form-select mb-3">
                    <?php foreach (KeuanganKategoriModel::JENIS as $kk => $v) : ?><option value="<?= $kk ?>" <?= $nilai('jenis', 'sekali') === $kk ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?>
                </select>
                <label class="form-label small fw-semibold" for="nominal_standar">Nominal standar (Rp, opsional)</label>
                <input type="text" inputmode="numeric" name="nominal_standar" id="nominal_standar" class="form-control mb-3" value="<?= esc((string) $nilai('nominal_standar')) ?>">
                <label class="form-label small fw-semibold" for="keterangan">Keterangan</label>
                <input type="text" name="keterangan" id="keterangan" class="form-control mb-3" maxlength="255" value="<?= esc((string) $nilai('keterangan')) ?>">
                <label class="form-label small fw-semibold" for="urutan">Urutan tampil</label>
                <input type="number" name="urutan" id="urutan" class="form-control mb-3" min="0" max="99" value="<?= esc((string) $nilai('urutan', 0)) ?>">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" <?= $nilai('is_active', 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-utama">Simpan</button>
                    <?php if ($k) : ?><a class="btn btn-outline-secondary" href="<?= site_url('admin/keuangan/kategori?punguan=' . $punguan['id']) ?>">Batal</a><?php endif ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
