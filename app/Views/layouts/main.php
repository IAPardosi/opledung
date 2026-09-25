<?php
$user       = auth()->loggedIn() ? auth()->user() : null;
$isAdmin    = $user?->can('admin.access') ?? false;
$isSuper    = $user?->inGroup('superadmin') ?? false;
$pending    = 0;
if ($isAdmin) {
    $q = db_connect()->table('change_requests')->where('status', 'pending');
    if (! $isSuper) {
        $q->where('marga_id', $user->marga_id);
    }
    $pending = $q->countAllResults();
}
$uri = service('uri')->getPath();
$aktif = static fn (string $awal): string => ($awal === '' ? $uri === '' || $uri === '/' : str_starts_with(ltrim($uri, '/'), $awal)) ? ' active' : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= trim($this->renderSection('title')) ?: 'Silsilah' ?> · Silsilah Marga</title>
    <link href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-silsilah">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('/') ?>"><i class="bi bi-diagram-3"></i> Silsilah Marga</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUtama" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navUtama">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $aktif('') ?>" href="<?= site_url('/') ?>">Beranda</a></li>
                <li class="nav-item"><a class="nav-link<?= $aktif('silsilah') ?>" href="<?= site_url('silsilah') ?>">Pohon Silsilah</a></li>
                <li class="nav-item"><a class="nav-link<?= $aktif('generasi') ?>" href="<?= site_url('generasi') ?>">Daftar Generasi</a></li>
                <?php if ($isAdmin) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= $aktif('admin') ?>" href="#" data-bs-toggle="dropdown">
                        Admin <?php if ($pending > 0) : ?><span class="badge rounded-pill bg-warning text-dark"><?= $pending ?></span><?php endif ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('admin/usulan') ?>"><i class="bi bi-inbox"></i> Usulan Data <?php if ($pending > 0) : ?><span class="badge bg-warning text-dark"><?= $pending ?></span><?php endif ?></a></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/import') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Import Excel</a></li>
                        <?php if ($user->can('silsilah.pokok')) : ?>
                        <li><a class="dropdown-item" href="<?= site_url('admin/leluhur-awal') ?>"><i class="bi bi-person-badge"></i> Leluhur Awal</a></li>
                        <?php endif ?>
                        <?php if ($isSuper) : ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/marga') ?>"><i class="bi bi-bookmark"></i> Marga</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('admin/pengguna') ?>"><i class="bi bi-people"></i> Pengguna</a></li>
                        <?php endif ?>
                    </ul>
                </li>
                <?php endif ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user !== null) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= esc($user->username) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= site_url('profil-saya') ?>">Profil Saya</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('usulan-saya') ?>">Usulan Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('logout') ?>">Keluar</a></li>
                    </ul>
                </li>
                <?php else : ?>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('login') ?>">Masuk</a></li>
                <li class="nav-item"><a class="btn btn-sm btn-aksen ms-lg-2 mt-1" href="<?= site_url('register') ?>">Daftar</a></li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        <?= view('partials/flash') ?>
    </div>
    <?= $this->renderSection('main') ?>
</main>

<footer class="footer-silsilah py-4 mt-5">
    <div class="container small text-body-secondary d-flex flex-wrap justify-content-between gap-2">
        <span>Silsilah Marga · Data pribadi dilindungi sesuai UU No. 27 Tahun 2022.</span>
        <span>Data silsilah diverifikasi oleh Ketua Adat dan Verifikator.</span>
    </div>
</footer>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
