<?php $v = static fn (string $k, $d = '') => old($k, $marga[$k] ?? $d); ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $marga ? 'Ubah Marga' : 'Tambah Marga' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 760px">
    <a href="<?= site_url('admin/marga') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar marga</a>
    <h1 class="h3 mt-2 mb-3"><?= $marga ? 'Ubah Marga ' . esc($marga['nama']) : 'Tambah Marga' ?></h1>
    <form method="post" class="card card-body">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="kode">Kode <span class="text-danger">*</span></label>
                <input class="form-control font-monospace text-uppercase" id="kode" name="kode" value="<?= esc($v('kode')) ?>" maxlength="5" required <?= $kunciKode ? 'disabled' : '' ?>>
                <div class="form-text"><?= $kunciKode ? 'Terkunci: sudah dipakai di kode anggota.' : '2–5 huruf, mis. PDS.' ?></div>
            </div>
            <div class="col-md-9">
                <label class="form-label" for="nama">Nama marga <span class="text-danger">*</span></label>
                <input class="form-control" id="nama" name="nama" value="<?= esc($v('nama')) ?>" maxlength="100" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="nama_rumpun">Rumpun / nama leluhur</label>
                <input class="form-control" id="nama_rumpun" name="nama_rumpun" value="<?= esc($v('nama_rumpun')) ?>" maxlength="150" placeholder="mis. Op. Ledung">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="asal_kampung">Asal kampung (bona pasogit)</label>
                <input class="form-control" id="asal_kampung" name="asal_kampung" value="<?= esc($v('asal_kampung')) ?>" maxlength="255">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="batas_silsilah_pokok">Silsilah Pokok sampai generasi</label>
                <input type="number" min="1" max="100" class="form-control" id="batas_silsilah_pokok" name="batas_silsilah_pokok" value="<?= esc($v('batas_silsilah_pokok', config('Silsilah')->batasSilsilahPokokBawaan)) ?>" required>
                <div class="form-text">Generasi 1 sampai angka ini hanya dapat diubah oleh Ketua Adat.</div>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $v('is_active', 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Aktif (tampil di situs)</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="sejarah">Sejarah singkat</label>
                <textarea class="form-control" id="sejarah" name="sejarah" rows="5"><?= esc($v('sejarah')) ?></textarea>
            </div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
