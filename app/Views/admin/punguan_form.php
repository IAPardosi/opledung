<?php
use App\Models\PunguanModel;

$v = static fn (string $k, $d = '') => old($k, $p[$k] ?? $d);
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $p ? 'Ubah Punguan' : 'Tambah Punguan' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 760px">
    <a href="<?= site_url('admin/punguan') ?>" class="small"><i class="bi bi-arrow-left"></i> Daftar punguan</a>
    <h1 class="h3 mt-2 mb-3"><?= $p ? 'Ubah ' . esc($p['nama']) : 'Tambah Punguan' ?></h1>
    <form method="post" class="card card-body p-4" id="wilayah" data-api="<?= site_url('api/wilayah') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-7"><label class="form-label" for="nama">Nama</label><input class="form-control" id="nama" name="nama" value="<?= esc($v('nama')) ?>" required maxlength="120" placeholder="mis. Punguan Jabodetabek"></div>
            <div class="col-md-5"><label class="form-label" for="tingkat">Tingkat</label>
                <select class="form-select" id="tingkat" name="tingkat"><?php foreach (PunguanModel::TINGKAT as $k => $l) : ?><option value="<?= $k ?>" <?= $v('tingkat', 'daerah') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
            <div class="col-md-6"><label class="form-label" for="wilayah_kode">Kab/kota cakupan (Indonesia)</label>
                <select class="form-select" id="wilayah_kode" name="wilayah_kode" data-kabupaten data-pilih="<?= esc($v('wilayah_kode'), 'attr') ?>"><option value="">– Tidak ada –</option></select></div>
            <div class="col-md-6"><label class="form-label" for="negara">Negara (punguan global)</label><input class="form-control" id="negara" name="negara" value="<?= esc($v('negara')) ?>" maxlength="80" placeholder="mis. Belanda"></div>
            <div class="col-md-6"><label class="form-label" for="induk_id">Induk</label>
                <select class="form-select" id="induk_id" name="induk_id"><option value="">– Tidak ada –</option><?php foreach ($semua as $s) : ?><option value="<?= $s['id'] ?>" <?= (string) $v('induk_id') === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['nama']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-6"><label class="form-label" for="kontak">Kontak</label><input class="form-control" id="kontak" name="kontak" value="<?= esc($v('kontak')) ?>" maxlength="150"></div>
            <div class="col-12"><label class="form-label" for="keterangan">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= esc($v('keterangan')) ?></textarea></div>
            <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $v('is_active', 1) ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Aktif</label></div>
        </div>
        <div class="text-end mt-3"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/pilih-kabupaten.js') ?>"></script>
<?= $this->endSection() ?>
