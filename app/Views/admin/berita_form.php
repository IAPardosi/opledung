<?php $v = static fn (string $k, $d = '') => old($k, $b[$k] ?? $d); ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $b ? 'Ubah Berita' : 'Tulis Berita' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 860px">
    <a href="<?= site_url('admin/berita') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kelola berita</a>
    <h1 class="h3 mt-2 mb-3"><?= $b ? 'Ubah Berita' : 'Tulis Berita' ?></h1>
    <form method="post" enctype="multipart/form-data" class="card card-body">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="judul">Judul <span class="text-danger">*</span></label>
                <input class="form-control form-control-lg" id="judul" name="judul" value="<?= esc($v('judul')) ?>" maxlength="200" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kategori">Kategori</label>
                <select class="form-select form-select-lg" id="kategori" name="kategori">
                    <?php foreach (config('Silsilah')->kategoriBerita as $k => $kat) : ?>
                        <option value="<?= $k ?>" <?= $v('kategori', 'berita') === $k ? 'selected' : '' ?>><?= esc($kat['label']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="ringkasan">Ringkasan</label>
                <input class="form-control" id="ringkasan" name="ringkasan" value="<?= esc($v('ringkasan')) ?>" maxlength="300" placeholder="Satu-dua kalimat yang tampil di kartu berita">
            </div>
            <div class="col-12">
                <label class="form-label" for="isi">Isi <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi" name="isi" rows="14" required><?= esc($v('isi')) ?></textarea>
                <div class="form-text">Pisahkan paragraf dengan baris kosong. Format: <code>**tebal**</code>, <code>*miring*</code>, <code>- daftar</code>, <code>[teks](https://tautan)</code>.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="gambar">Gambar sampul</label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
                <?php if ($b['gambar'] ?? null) : ?>
                    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="hapus_gambar" value="1" id="hapus_gambar"><label class="form-check-label small" for="hapus_gambar">Hapus gambar sekarang</label></div>
                <?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="draft" <?= $v('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draf (belum tampil)</option>
                    <option value="terbit" <?= $v('status') === 'terbit' ? 'selected' : '' ?>>Terbitkan</option>
                </select>
            </div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
