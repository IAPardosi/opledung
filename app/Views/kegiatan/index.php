<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kegiatan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <p class="eyebrow mb-1">Agenda punguan</p>
    <div class="judul-bagian"><h1 class="h2">Kegiatan</h1></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="h5 mb-3">Akan datang</h2>
            <div class="card kartu-aksen">
                <div class="card-body py-2">
                    <?php if ($akanDatang === []) : ?><p class="text-teks-2 my-2">Belum ada kegiatan terjadwal.</p><?php endif ?>
                    <?php foreach ($akanDatang as $i => $k) : ?>
                        <?php if ($i > 0) : ?><hr class="my-1"><?php endif ?>
                        <?= view('partials/baris_kegiatan', ['k' => $k]) ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <h2 class="h5 mb-3">Sudah berlangsung</h2>
            <div class="card">
                <div class="card-body py-2">
                    <?php if ($lewat === []) : ?><p class="text-teks-2 my-2">Belum ada.</p><?php endif ?>
                    <?php foreach ($lewat as $i => $k) : ?>
                        <?php if ($i > 0) : ?><hr class="my-1"><?php endif ?>
                        <?= view('partials/baris_kegiatan', ['k' => $k]) ?>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
