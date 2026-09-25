<?php
$user     = auth()->loggedIn() ? auth()->user() : null;
$isAdmin  = $user?->can('admin.access') ?? false;
$isSuper  = $user?->inGroup('superadmin') ?? false;
$isCalon  = $user?->inGroup('calon') ?? false;
$pending  = 0;
$saksi    = 0;
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
    $saksi = count(array_filter((new \App\Services\UsulanService())->menungguKesaksian($user), static fn ($r) => $r['kesaksian_saya'] === null));
}
$margaNav = (new \App\Models\MargaModel())->where('is_active', 1)->orderBy('id')->first();
$uri   = trim(service('uri')->getPath(), '/');
$aktif = static fn (string ...$awal): string => array_filter($awal, static fn ($a) => $a === '' ? $uri === '' : str_starts_with($uri, $a)) ? ' active' : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= trim($this->renderSection('title')) ?: 'Silsilah' ?> · Silsilah Marga</title>
    <meta name="theme-color" content="#17110f">
    <link rel="icon" href="<?= base_url('assets/img/logo.svg') ?>" type="image/svg+xml">
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-silsilah">
    <div class="container">
        <a class="navbar-brand" href="<?= site_url('/') ?>">
            <img src="<?= base_url('assets/img/logo.svg') ?>" alt="">
            <span>Tarombo <?= esc($margaNav['nama'] ?? 'Marga') ?><small><?= esc($margaNav['nama_rumpun'] ?? 'Silsilah · Partuturan · Informasi') ?></small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUtama" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navUtama">
            <ul class="navbar-nav me-auto ms-lg-3">
                <li class="nav-item"><a class="nav-link<?= $aktif('') ?>" href="<?= site_url('/') ?>">Beranda</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= $aktif('silsilah', 'generasi', 'partuturan', 'hubungan') ?>" href="#" data-bs-toggle="dropdown">Silsilah</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('silsilah') ?>"><i class="bi bi-diagram-3"></i> Pohon Silsilah</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('generasi') ?>"><i class="bi bi-list-ol"></i> Daftar Generasi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('hubungan') ?>"><i class="bi bi-people"></i> Cek Partuturan</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('partuturan') ?>"><i class="bi bi-book"></i> Kamus Partuturan</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link<?= $aktif('berita') ?>" href="<?= site_url('berita') ?>">Berita</a></li>
                <li class="nav-item"><a class="nav-link<?= $aktif('kegiatan') ?>" href="<?= site_url('kegiatan') ?>">Kegiatan</a></li>
                <?php if ($isAdmin) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= $aktif('admin') ?>" href="#" data-bs-toggle="dropdown">
                        Admin <?php if ($pending > 0) : ?><span class="badge rounded-pill text-bg-danger"><?= $pending ?></span><?php endif ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ($user->can('silsilah.verify')) : ?>
                        <li><a class="dropdown-item" href="<?= site_url('admin/usulan') ?>"><i class="bi bi-inbox"></i> Pendaftaran & Usulan <?php if ($pending > 0) : ?><span class="badge text-bg-danger"><?= $pending ?></span><?php endif ?></a></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/import') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Import Excel</a></li>
                        <?php endif ?>
                        <?php if ($user->can('silsilah.pokok')) : ?>
                        <li><a class="dropdown-item" href="<?= site_url('admin/leluhur-awal') ?>"><i class="bi bi-person-badge"></i> Leluhur Awal</a></li>
                        <?php endif ?>
                        <?php if ($user->can('partuturan.kelola')) : ?>
                        <li><a class="dropdown-item" href="<?= site_url('admin/partuturan') ?>"><i class="bi bi-chat-quote"></i> Istilah Partuturan</a></li>
                        <?php endif ?>
                        <?php if ($user->can('konten.kelola')) : ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/berita') ?>"><i class="bi bi-newspaper"></i> Kelola Berita</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/kegiatan') ?>"><i class="bi bi-calendar-event"></i> Kelola Kegiatan</a></li>
                        <?php endif ?>
                        <?php if ($isSuper) : ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/marga') ?>"><i class="bi bi-bookmark"></i> Marga</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/pengguna') ?>"><i class="bi bi-person-gear"></i> Pengguna & Lingkup</a></li>
                        <?php endif ?>
                    </ul>
                </li>
                <?php endif ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user !== null) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= esc($user->username) ?>
                        <?php if ($saksi > 0) : ?><span class="badge rounded-pill text-bg-danger"><?= $saksi ?></span><?php endif ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($isCalon) : ?>
                            <li><a class="dropdown-item" href="<?= site_url('pendaftaran') ?>"><i class="bi bi-hourglass-split"></i> Status Pendaftaran</a></li>
                        <?php else : ?>
                            <li><a class="dropdown-item" href="<?= site_url('profil-saya') ?>"><i class="bi bi-person-vcard"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('hubungan') ?>"><i class="bi bi-people"></i> Cek Partuturan</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('konfirmasi-keluarga') ?>"><i class="bi bi-patch-check"></i> Kesaksian Keluarga <?php if ($saksi > 0) : ?><span class="badge text-bg-danger"><?= $saksi ?></span><?php endif ?></a></li>
                            <li><a class="dropdown-item" href="<?= site_url('usulan-saya') ?>"><i class="bi bi-send"></i> Usulan Saya</a></li>
                        <?php endif ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                    </ul>
                </li>
                <?php else : ?>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('login') ?>">Masuk</a></li>
                <li class="nav-item"><a class="btn btn-sm btn-utama ms-lg-2 mt-1 px-3" href="<?= site_url('register') ?>">Daftar Member</a></li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</nav>
<div class="ipon"></div>

<?php if ($isCalon && trim($uri, '/') !== 'pendaftaran') : ?>
<div class="banner-calon py-2">
    <div class="container small d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span><i class="bi bi-hourglass-split text-utama"></i> Akun Anda belum terverifikasi. Lengkapi data silsilah agar dapat melihat profil dan partuturan keluarga.</span>
        <a class="btn btn-sm btn-utama" href="<?= site_url('pendaftaran') ?>">Lengkapi pendaftaran</a>
    </div>
</div>
<?php endif ?>

<main class="pb-5">
    <div class="container pt-3">
        <?= view('partials/flash') ?>
    </div>
    <?= $this->renderSection('main') ?>
</main>

<footer class="footer-silsilah">
    <div class="ipon ipon-balik"></div>
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= base_url('assets/img/logo.svg') ?>" width="40" height="40" alt="">
                    <span class="motto">Dalihan Na Tolu</span>
                </div>
                <p class="motto mb-1">Somba marhula-hula, <span>elek marboru,</span> manat mardongan tubu.</p>
                <p class="small mb-0">Hormat kepada hula-hula, mengasihi boru, dan hati-hati kepada saudara semarga.</p>
            </div>
            <div class="col-6 col-lg-2 offset-lg-1 small">
                <div class="fw-semibold text-white mb-2">Silsilah</div>
                <ul class="list-unstyled d-grid gap-1 mb-0">
                    <li><a href="<?= site_url('silsilah') ?>">Pohon Silsilah</a></li>
                    <li><a href="<?= site_url('generasi') ?>">Daftar Generasi</a></li>
                    <li><a href="<?= site_url('partuturan') ?>">Kamus Partuturan</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2 small">
                <div class="fw-semibold text-white mb-2">Informasi</div>
                <ul class="list-unstyled d-grid gap-1 mb-0">
                    <li><a href="<?= site_url('berita') ?>">Berita</a></li>
                    <li><a href="<?= site_url('kegiatan') ?>">Kegiatan</a></li>
                    <li><a href="<?= site_url('register') ?>">Daftar Member</a></li>
                </ul>
            </div>
            <div class="col-lg-2 small">
                <div class="fw-semibold text-white mb-2">Privasi</div>
                <p class="mb-0">Data pribadi dilindungi sesuai UU No. 27 Tahun 2022. Silsilah divalidasi Ketua Adat dan Admin Wilayah.</p>
            </div>
        </div>
    </div>
    <div class="ulos"></div>
</footer>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
