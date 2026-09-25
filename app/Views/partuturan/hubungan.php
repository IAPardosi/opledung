<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Cek Partuturan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 980px">
    <p class="eyebrow mb-1">Partuturan</p>
    <div class="judul-bagian"><h1 class="h2">Cek Partuturan</h1></div>
    <p class="text-teks-2">Pilih dua anggota untuk melihat bagaimana yang satu memanggil yang lain, lengkap dengan jalur silsilah dan titik temunya.
        <a href="<?= site_url('partuturan') ?>">Lihat kamus partuturan</a>.</p>

    <?php if (! $tautan) : ?>
        <div class="alert alert-warning small"><i class="bi bi-info-circle"></i> Akun Anda belum tertaut ke data silsilah, jadi "dari" belum otomatis terisi diri Anda. Cari nama Anda lalu tekan "Ini saya" di profil.</div>
    <?php endif ?>

    <form method="get" action="<?= site_url('hubungan') ?>" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <?php foreach (['dari' => [$dari, 'Dari (yang memanggil)'], 'ke' => [$ke, 'Kepada (yang dipanggil)']] as $nama => [$orang, $label]) : ?>
            <div class="col-md-5 position-relative">
                <label class="form-label small fw-semibold" for="cari_<?= $nama ?>"><?= $label ?></label>
                <input type="hidden" name="<?= $nama ?>" id="<?= $nama ?>" value="<?= $orang?->id ?>">
                <input type="search" class="form-control pilih-orang" id="cari_<?= $nama ?>" data-target="<?= $nama ?>" autocomplete="off"
                       value="<?= $orang ? esc($orang->nama_lengkap . ' · ' . $orang->kode_anggota, 'attr') : '' ?>" placeholder="Ketik nama atau kode…">
                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:20"></div>
            </div>
            <?php endforeach ?>
            <div class="col-md-2 d-grid"><button class="btn btn-utama"><i class="bi bi-arrow-left-right"></i> Cek</button></div>
        </div>
    </form>

    <?php if ($dari && $ke) : ?>
        <?php if ($hasil === null) : ?>
            <div class="card card-body text-center py-4"><i class="bi bi-question-circle fs-2 text-utama"></i><p class="mb-0 mt-2">Hubungan keduanya belum ditemukan dalam silsilah yang tercatat.</p></div>
        <?php else : ?>
            <?= view('partials/tutur', ['hasil' => $hasil, 'dari' => $dari, 'ke' => $ke, 'diri' => $diri]) ?>
            <p class="small text-teks-2 mt-2"><i class="bi bi-info-circle"></i> Istilah ditetapkan Ketua Adat dan dapat berbeda antardaerah. Haha–anggi ditentukan dari garis yang lebih sulung, bukan dari umur.</p>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    window.SILSILAH_URL = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;
</script>
<script src="<?= base_url('assets/js/pilih-orang.js') ?>"></script>
<?= $this->endSection() ?>
