<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Profil Saya<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 720px">
    <div class="card card-body text-center py-5">
        <i class="bi bi-person-bounding-box fs-1 text-utama"></i>
        <h1 class="h4 mt-3">Akun Anda belum tertaut ke data silsilah</h1>
        <p class="text-body-secondary">Cari nama Anda di daftar generasi, buka profilnya, lalu tekan tombol <b>"Ini saya"</b>. Verifikator akan memeriksa dan menautkan akun Anda.</p>
        <p class="text-body-secondary small">Bila nama Anda belum ada, buka profil ayah Anda dan pilih <b>"Usulkan"</b> pada bagian Anak.</p>
        <form action="<?= site_url('generasi') ?>" method="get" class="d-flex gap-2 mx-auto mt-2" style="max-width: 460px">
            <input type="search" name="q" class="form-control" placeholder="Nama Anda atau nama ayah Anda…" required>
            <button class="btn btn-utama"><i class="bi bi-search"></i> Cari</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
