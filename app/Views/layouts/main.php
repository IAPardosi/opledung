<?php
$user     = auth()->loggedIn() ? auth()->user() : null;
$isAdmin  = $user?->can('admin.access') ?? false;
$isSuper  = $user?->inGroup('superadmin') ?? false;
$isCalon  = $user?->inGroup('calon') ?? false;
$pending  = 0;
$tugas    = 0;
if ($isAdmin && $user->can('silsilah.verify')) {
    $q = new \App\Models\ChangeRequestModel();
    $q->where('status', 'pending');
    if (! $isSuper) {
        $q->where('marga_id', $user->marga_id);
    }
    (new \App\Services\LingkupAdmin())->saringUsulan($q->builder(), $user);
    $pending = $q->countAllResults();
}
if ($user?->person_id !== null && ($user?->can('silsilah.view') ?? false)) {
    $tugas = (new \App\Services\UsulanService())->jumlahTugasKeluarga($user);
}
$margaNav = (new \App\Models\MargaModel())->where('is_active', 1)->orderBy('id')->first();
$uri   = trim(service('uri')->getPath(), '/');
$aktif = static fn (string ...$awal): string => array_filter($awal, static fn ($a) => $a === '' ? $uri === '' : str_starts_with($uri, $a)) ? ' active' : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= trim($this->renderSection('title')) ?: 'Silsilah' ?> · Tarombo <?= esc($margaNav['nama'] ?? 'Marga') ?></title>
    <meta name="theme-color" content="#f6f3f0">
    <link rel="icon" href="<?= base_url('assets/img/logo.svg') ?>" type="image/svg+xml">
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body>
<div class="nav-kapsul-wrap">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-silsilah">
            <a class="navbar-brand me-lg-4" href="<?= site_url('/') ?>">
                <img src="<?= base_url('assets/img/logo.svg') ?>" alt="">
                <span>Tarombo <?= esc($margaNav['nama'] ?? 'Marga') ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUtama" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navUtama">
                <ul class="navbar-nav me-auto gap-lg-1">
                    <li class="nav-item"><a class="nav-link<?= $aktif('') ?>" href="<?= site_url('/') ?>">Beranda</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $aktif('silsilah', 'generasi', 'keluarga-dekat', 'garis') ?>" href="#" data-bs-toggle="dropdown">Silsilah</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('garis') ?>"><i class="bi bi-signpost-split me-2"></i>Jalur saya</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('keluarga-dekat') ?>"><i class="bi bi-people me-2"></i>Keluarga dekat</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('generasi') ?>"><i class="bi bi-list-ol me-2"></i>Per sundut</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('silsilah') ?>"><i class="bi bi-diagram-3 me-2"></i>Pohon cabang</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link<?= $aktif('kenali-marga') ?>" href="<?= site_url('kenali-marga') ?>">Kenali Marga</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $aktif('partuturan', 'hubungan') ?>" href="#" data-bs-toggle="dropdown">Partuturan</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('hubungan') ?>"><i class="bi bi-arrow-left-right me-2"></i>Cek partuturan</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('partuturan') ?>"><i class="bi bi-book me-2"></i>Kamus partuturan</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $aktif('berita', 'kegiatan', 'punguan') ?>" href="#" data-bs-toggle="dropdown">Kabar</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('berita') ?>"><i class="bi bi-newspaper me-2"></i>Berita</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('kegiatan') ?>"><i class="bi bi-calendar-event me-2"></i>Kegiatan</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('punguan') ?>"><i class="bi bi-geo-alt me-2"></i>Punguan</a></li>
                        </ul>
                    </li>
                    <?php if ($isAdmin) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $aktif('admin') ?>" href="#" data-bs-toggle="dropdown">
                            Admin <?php if ($pending > 0) : ?><span class="badge text-bg-danger"><?= $pending ?></span><?php endif ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($user->can('silsilah.verify')) : ?>
                            <li><a class="dropdown-item" href="<?= site_url('admin/usulan') ?>"><i class="bi bi-inbox me-2"></i>Pendaftaran & usulan <?php if ($pending > 0) : ?><span class="badge text-bg-danger"><?= $pending ?></span><?php endif ?></a></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/import') ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Excel</a></li>
                            <?php endif ?>
                            <?php if ($user->can('silsilah.pokok')) : ?>
                            <li><a class="dropdown-item" href="<?= site_url('admin/leluhur-awal') ?>"><i class="bi bi-person-badge me-2"></i>Leluhur awal</a></li>
                            <?php endif ?>
                            <?php if ($user->can('partuturan.kelola')) : ?>
                            <li><a class="dropdown-item" href="<?= site_url('admin/partuturan') ?>"><i class="bi bi-chat-quote me-2"></i>Istilah partuturan</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/sejarah') ?>"><i class="bi bi-journal-text me-2"></i>Sejarah marga</a></li>
                            <?php endif ?>
                            <?php if ($user->can('konten.kelola')) : ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/berita') ?>"><i class="bi bi-newspaper me-2"></i>Kelola berita</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/kegiatan') ?>"><i class="bi bi-calendar-event me-2"></i>Kelola kegiatan</a></li>
                            <?php endif ?>
                            <?php if ($isSuper) : ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/marga') ?>"><i class="bi bi-bookmark me-2"></i>Marga</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/punguan') ?>"><i class="bi bi-geo-alt me-2"></i>Punguan</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('admin/pengguna') ?>"><i class="bi bi-person-gear me-2"></i>Pengguna & lingkup</a></li>
                            <?php endif ?>
                        </ul>
                    </li>
                    <?php endif ?>
                </ul>
                <ul class="navbar-nav align-items-lg-center gap-1">
                    <?php if ($user !== null) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= esc($user->username) ?>
                            <?php if ($tugas > 0) : ?><span class="badge text-bg-danger"><?= $tugas ?></span><?php endif ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($isCalon) : ?>
                                <li><a class="dropdown-item" href="<?= site_url('pendaftaran') ?>"><i class="bi bi-hourglass-split me-2"></i>Status pendaftaran</a></li>
                            <?php else : ?>
                                <li><a class="dropdown-item" href="<?= site_url('profil-saya') ?>"><i class="bi bi-person-vcard me-2"></i>Profil saya</a></li>
                                <li><a class="dropdown-item" href="<?= site_url('konfirmasi-keluarga') ?>"><i class="bi bi-patch-check me-2"></i>Validasi keluarga <?php if ($tugas > 0) : ?><span class="badge text-bg-danger"><?= $tugas ?></span><?php endif ?></a></li>
                                <li><a class="dropdown-item" href="<?= site_url('usulan-saya') ?>"><i class="bi bi-send me-2"></i>Usulan saya</a></li>
                            <?php endif ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </li>
                    <?php else : ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('login') ?>">Masuk</a></li>
                    <li class="nav-item"><a class="btn btn-utama" href="<?= site_url('register') ?>">Daftar member</a></li>
                    <?php endif ?>
                </ul>
            </div>
        </nav>
    </div>
</div>

<?php if ($isCalon && $uri !== 'pendaftaran') : ?>
<div class="container mt-3">
    <div class="banner-calon px-3 py-2 small d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span><i class="bi bi-hourglass-split text-utama"></i> Akun Anda belum disahkan. Lengkapi data keluarga Anda agar dapat melihat profil dan partuturan.</span>
        <a class="btn btn-sm btn-utama" href="<?= site_url('pendaftaran') ?>">Lengkapi pendaftaran</a>
    </div>
</div>
<?php endif ?>

<main class="pb-4">
    <div class="container pt-3">
        <?= view('partials/flash') ?>
    </div>
    <?= $this->renderSection('main') ?>
</main>

<footer class="footer-silsilah">
    <div class="container py-5">
        <div class="row g-4 align-items-end">
            <div class="col-lg-7">
                <p class="motto mb-2">Somba marhula-hula, <span>elek marboru,</span> manat mardongan tubu.</p>
                <p class="small mb-0">Dalihan Na Tolu · Data pribadi dilindungi UU No. 27 Tahun 2022 · Silsilah disahkan Ketua Adat dan penatua punguan.</p>
            </div>
            <div class="col-lg-5 small d-flex flex-wrap gap-3 justify-content-lg-end">
                <a href="<?= site_url('kenali-marga') ?>">Kenali Marga</a>
                <a href="<?= site_url('silsilah') ?>">Silsilah</a>
                <a href="<?= site_url('partuturan') ?>">Partuturan</a>
                <a href="<?= site_url('punguan') ?>">Punguan</a>
                <a href="<?= site_url('berita') ?>">Kabar</a>
            </div>
        </div>
    </div>
</footer>

<nav class="nav-bawah" aria-label="Navigasi utama">
    <a href="<?= site_url('/') ?>" class="<?= trim($aktif('')) ?>"><i class="bi bi-house"></i>Beranda</a>
    <a href="<?= site_url('garis') ?>" class="<?= trim($aktif('silsilah', 'generasi', 'keluarga-dekat', 'garis')) ?>"><i class="bi bi-diagram-3"></i>Silsilah</a>
    <a href="<?= site_url('hubungan') ?>" class="<?= trim($aktif('hubungan', 'partuturan')) ?>"><i class="bi bi-arrow-left-right"></i>Tutur</a>
    <a href="<?= site_url('berita') ?>" class="<?= trim($aktif('berita', 'kegiatan', 'punguan')) ?>"><i class="bi bi-calendar-event"></i>Kabar</a>
    <a href="<?= site_url($user ? ($isCalon ? 'pendaftaran' : 'profil-saya') : 'login') ?>" class="<?= trim($aktif('profil-saya', 'pendaftaran', 'login', 'anggota')) ?>"><i class="bi bi-person"></i>Akun</a>
</nav>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
