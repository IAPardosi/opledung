<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $mode === 'garis' ? 'Jalur Saya' : 'Keluarga Dekat' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 760px">
    <div class="mb-4"><?= view('partials/mode_tampil', ['aktif' => $mode === 'garis' ? 'garis' : 'keluarga']) ?></div>
    <div class="card card-body text-center py-5">
        <i class="bi bi-person-bounding-box fs-1 text-utama"></i>
        <h1 class="h4 mt-3">Akun Anda belum tertaut ke data silsilah</h1>
        <p class="text-teks-2">Cari nama Anda lalu tekan <b>"Ini saya"</b>, atau daftarkan keluarga Anda. Setelah disahkan, jalur Anda dari Sundut 1 langsung tampil di sini.</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="<?= site_url('pendaftaran') ?>" class="btn btn-utama">Daftarkan keluarga</a>
            <a href="<?= site_url('generasi') ?>" class="btn btn-outline-secondary">Cari nama saya</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
