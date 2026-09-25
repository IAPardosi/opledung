<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Beranda<?= $this->endSection() ?>

<?= $this->section('main') ?>
<section class="hero py-5 mb-4" style="margin-top:-1.5rem">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p class="text-uppercase small mb-1" style="letter-spacing:.1em;color:var(--warna-aksen)">Silsilah Marga</p>
                <h1 class="display-5 fw-bold mb-2"><?= esc($marga['nama']) ?></h1>
                <?php if ($marga['nama_rumpun']) : ?><p class="lead mb-3">Rumpun <?= esc($marga['nama_rumpun']) ?><?= $marga['asal_kampung'] ? ' · ' . esc($marga['asal_kampung']) : '' ?></p><?php endif ?>
                <p class="mb-4" style="color:rgba(255,255,255,.8)">Telusuri garis keturunan dari leluhur awal hingga generasi sekarang: siapa keturunan siapa, dan dari cabang mana.</p>
                <form action="<?= site_url('generasi') ?>" method="get" class="d-flex gap-2" role="search">
                    <input type="search" name="q" class="form-control form-control-lg" placeholder="Cari nama atau kode anggota…" aria-label="Cari anggota">
                    <button class="btn btn-aksen btn-lg px-4" type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6"><div class="stat"><div class="angka"><?= number_format($total, 0, ',', '.') ?></div><div class="label">Anggota garis marga</div></div></div>
                    <div class="col-6"><div class="stat"><div class="angka"><?= count($rekap) ?></div><div class="label">Generasi tercatat</div></div></div>
                    <div class="col-6"><div class="stat"><div class="angka"><?= number_format($hidup, 0, ',', '.') ?></div><div class="label">Anggota yang hidup</div></div></div>
                    <div class="col-6"><div class="stat"><div class="angka"><?= $aktif ? esc($aktif[0] . '–' . $aktif[1]) : '–' ?></div><div class="label">Generasi aktif</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">Leluhur Awal</div>
                <div class="card-body">
                    <?php if ($leluhur) : ?>
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <span class="avatar avatar-sm"><?= esc(inisial($leluhur->nama_lengkap)) ?></span>
                            <div>
                                <div class="fw-semibold"><?= esc($leluhur->gelar_adat ?: $leluhur->nama_lengkap) ?></div>
                                <div class="small text-body-secondary"><?= esc($leluhur->nama_lengkap) ?> · Generasi 1</div>
                            </div>
                        </div>
                        <?php if ($marga['sejarah']) : ?><p class="small"><?= nl2br(esc($marga['sejarah'])) ?></p><?php endif ?>
                        <a href="<?= site_url('silsilah') ?>" class="btn btn-utama w-100"><i class="bi bi-diagram-3"></i> Buka Pohon Silsilah</a>
                    <?php else : ?>
                        <p class="text-body-secondary mb-0">Leluhur awal (Generasi 1) belum ditetapkan oleh Ketua Adat.</p>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Anggota per Generasi</span>
                    <span class="small text-body-secondary">
                        <i class="d-inline-block rounded-1 garis-utama" style="width:.7rem;height:.7rem"></i> Garis utama
                        <i class="d-inline-block rounded-1 garis-boru ms-2" style="width:.7rem;height:.7rem"></i> Boru
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($rekap === []) : ?>
                        <p class="text-body-secondary mb-0">Belum ada data.</p>
                    <?php endif ?>
                    <?php foreach ($rekap as $r) : ?>
                        <a class="row g-2 align-items-center text-decoration-none text-body mb-1" href="<?= site_url('generasi?g=' . $r['generasi_ke']) ?>">
                            <div class="col-3 col-sm-2 small fw-medium">Generasi <?= $r['generasi_ke'] ?></div>
                            <div class="col">
                                <div class="bar-generasi" title="<?= $r['utama'] ?> garis utama, <?= $r['boru'] ?> boru">
                                    <span class="garis-utama" style="width:<?= round($r['utama'] / $maks * 100, 1) ?>%"></span>
                                    <span class="garis-boru" style="width:<?= round($r['boru'] / $maks * 100, 1) ?>%"></span>
                                </div>
                            </div>
                            <div class="col-3 col-sm-2 small text-end text-body-secondary"><?= number_format($r['utama'] + $r['boru'], 0, ',', '.') ?> orang</div>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
