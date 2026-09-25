<?php $jenis = config('Silsilah')->jenisKegiatan[$k['jenis']] ?? $k['jenis']; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($k['judul']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 960px">
    <a href="<?= site_url('kegiatan') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Semua kegiatan</a>
    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="kategori text-utama"><?= esc($jenis) ?></div>
            <h1 class="display-6 mt-1"><?= esc($k['judul']) ?></h1>
            <?php if ($k['status'] === 'batal') : ?><div class="alert alert-danger">Kegiatan ini <b>dibatalkan</b>.</div><?php endif ?>
            <?php if ($k['gambar']) : ?><img src="<?= base_url('uploads/konten/' . $k['gambar']) ?>" class="img-fluid rounded-4 my-3 w-100" alt=""><?php endif ?>
            <div class="isi-artikel mt-3"><?= format_isi($k['deskripsi']) ?: '<p class="text-teks-2">Belum ada keterangan.</p>' ?></div>
        </div>
        <div class="col-lg-4">
            <div class="tutur">
                <div class="ipon-kecil"></div>
                <div class="isi">
                    <div class="label">Waktu</div>
                    <p class="mb-3"><?= esc(tanggal_waktu_indo($k['mulai'])) ?><?php if ($k['selesai']) : ?><br><span class="ket">s.d. <?= esc(tanggal_waktu_indo($k['selesai'])) ?></span><?php endif ?></p>
                    <div class="label">Tempat</div>
                    <p class="mb-3"><?= esc($k['lokasi']) ?><?php if ($k['alamat']) : ?><br><span class="ket"><?= esc($k['alamat']) ?></span><?php endif ?><?php if ($wilayah) : ?><br><span class="ket"><?= esc($wilayah['nama']) ?></span><?php endif ?></p>
                    <?php if ($k['kontak']) : ?><div class="label">Narahubung</div><p class="mb-3"><?= esc($k['kontak']) ?></p><?php endif ?>
                    <?php if ($k['peta_url']) : ?><a href="<?= esc($k['peta_url'], 'attr') ?>" target="_blank" rel="noopener" class="btn btn-putih btn-sm w-100"><i class="bi bi-geo-alt"></i> Buka peta</a><?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
