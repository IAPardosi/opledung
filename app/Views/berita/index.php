<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Berita<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <p class="eyebrow mb-1">Kanal informasi</p>
    <div class="judul-bagian"><h1 class="h2">Berita & Pengumuman</h1></div>
    <ul class="nav nav-pills mb-4 gap-1">
        <li class="nav-item"><a class="nav-link<?= $kategori === null ? ' active' : '' ?>" href="<?= site_url('berita') ?>">Semua</a></li>
        <?php foreach (config('Silsilah')->kategoriBerita as $k => $v) : ?>
            <li class="nav-item"><a class="nav-link<?= $kategori === $k ? ' active' : '' ?>" href="<?= site_url('berita?kategori=' . $k) ?>"><i class="bi <?= $v['ikon'] ?>"></i> <?= esc($v['label']) ?></a></li>
        <?php endforeach ?>
    </ul>
    <?php if ($rows === []) : ?>
        <div class="card card-body text-teks-2">Belum ada berita pada kategori ini.</div>
    <?php endif ?>
    <div class="row g-4">
        <?php foreach ($rows as $b) : ?>
            <div class="col-md-6 col-lg-4"><?= view('partials/kartu_berita', ['b' => $b]) ?></div>
        <?php endforeach ?>
    </div>
    <div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>
</div>
<?= $this->endSection() ?>
