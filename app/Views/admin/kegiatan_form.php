<?php
$v     = static fn (string $key, $d = '') => old($key, $k[$key] ?? $d);
$lokal = static fn (?string $w): string => $w ? str_replace(' ', 'T', substr($w, 0, 16)) : '';
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $k ? 'Ubah Kegiatan' : 'Tambah Kegiatan' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 860px">
    <a href="<?= site_url('admin/kegiatan') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kelola kegiatan</a>
    <h1 class="h3 mt-2 mb-3"><?= $k ? 'Ubah Kegiatan' : 'Tambah Kegiatan' ?></h1>
    <form method="post" enctype="multipart/form-data" class="card card-body">
        <?= csrf_field() ?>
        <div class="row g-3" id="wilayah" data-api="<?= site_url('api/wilayah') ?>">
            <div class="col-md-8">
                <label class="form-label" for="judul">Nama kegiatan <span class="text-danger">*</span></label>
                <input class="form-control form-control-lg" id="judul" name="judul" value="<?= esc($v('judul')) ?>" maxlength="200" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="jenis">Jenis</label>
                <select class="form-select form-select-lg" id="jenis" name="jenis">
                    <?php foreach (config('Silsilah')->jenisKegiatan as $key => $label) : ?>
                        <option value="<?= $key ?>" <?= $v('jenis', 'pesta_adat') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mulai">Mulai <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" id="mulai" name="mulai" value="<?= esc(old('mulai', $lokal($k['mulai'] ?? null))) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="selesai">Selesai</label>
                <input type="datetime-local" class="form-control" id="selesai" name="selesai" value="<?= esc(old('selesai', $lokal($k['selesai'] ?? null))) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="lokasi">Tempat <span class="text-danger">*</span></label>
                <input class="form-control" id="lokasi" name="lokasi" value="<?= esc($v('lokasi')) ?>" maxlength="200" required placeholder="mis. Gedung Serbaguna, Sopo Godang">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="kabupaten_kode">Kabupaten/Kota</label>
                <select class="form-select" id="kabupaten_kode" name="kabupaten_kode" data-pilih="<?= esc($v('kabupaten_kode'), 'attr') ?>">
                    <option value="">– Pilih –</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="alamat">Alamat</label>
                <input class="form-control" id="alamat" name="alamat" value="<?= esc($v('alamat')) ?>" maxlength="255">
            </div>
            <div class="col-md-7">
                <label class="form-label" for="peta_url">Tautan peta (Google Maps)</label>
                <input type="url" class="form-control" id="peta_url" name="peta_url" value="<?= esc($v('peta_url')) ?>" placeholder="https://maps.app.goo.gl/…">
            </div>
            <div class="col-md-5">
                <label class="form-label" for="kontak">Narahubung</label>
                <input class="form-control" id="kontak" name="kontak" value="<?= esc($v('kontak')) ?>" maxlength="150" placeholder="Nama · No. HP">
            </div>
            <div class="col-12">
                <label class="form-label" for="deskripsi">Keterangan</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="8"><?= esc($v('deskripsi')) ?></textarea>
                <div class="form-text">Susunan acara, tata busana (mis. ulos), iuran, dan lain-lain. Format sama seperti berita.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="gambar">Gambar / undangan</label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="draft" <?= $v('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draf (belum tampil)</option>
                    <option value="terbit" <?= $v('status') === 'terbit' ? 'selected' : '' ?>>Terbitkan</option>
                    <option value="batal" <?= $v('status') === 'batal' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/pilih-kabupaten.js') ?>"></script>
<?= $this->endSection() ?>
