<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Horas!<?= $this->endSection() ?>

<?= $this->section('main') ?>
<section class="hero pola-gorga" style="margin-top:-1rem">
    <div class="container py-5">
        <div class="row align-items-center g-4 py-lg-4">
            <div class="col-lg-7">
                <div class="horas mb-2">Horas jala gabe!</div>
                <h1 class="mb-3">Tarombo<br><?= esc($marga['nama']) ?></h1>
                <p class="lead mb-4" style="max-width: 36rem">
                    <?php if ($marga['nama_rumpun']) : ?>Pomparan <?= esc($marga['nama_rumpun']) ?>. <?php endif ?>
                    Telusuri garis keturunan dari leluhur hingga sundut sekarang, ketahui partuturan (cara memanggil) sesama keluarga,
                    dan ikuti kabar serta kegiatan punguan.
                </p>
                <form action="<?= site_url('generasi') ?>" method="get" class="hero-cari d-flex gap-2 mb-3" role="search" style="max-width: 34rem">
                    <input type="search" name="q" class="form-control form-control-lg" placeholder="Cari nama atau kode anggota…" aria-label="Cari anggota">
                    <button class="btn btn-utama btn-lg px-4" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
                </form>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= site_url('silsilah') ?>" class="btn btn-putih"><i class="bi bi-diagram-3"></i> Buka Pohon Silsilah</a>
                    <?php if (! auth()->loggedIn()) : ?>
                        <a href="<?= site_url('register') ?>" class="btn btn-garis-putih"><i class="bi bi-person-plus"></i> Daftar sebagai Member</a>
                    <?php else : ?>
                        <a href="<?= site_url('hubungan') ?>" class="btn btn-garis-putih"><i class="bi bi-people"></i> Cek Partuturan</a>
                    <?php endif ?>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <img src="<?= base_url('assets/img/rumah-bolon.svg') ?>" class="rumah" alt="Ilustrasi Rumah Bolon">
            </div>
        </div>
    </div>
</section>
<div class="ulos"></div>

<div class="container" style="margin-top: -1.5rem; position: relative">
    <div class="row g-3">
        <div class="col-6 col-lg-3"><div class="stat"><div class="angka"><?= number_format($total, 0, ',', '.') ?></div><div class="label">Anggota garis marga</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="angka"><?= count($rekap) ?></div><div class="label">Sundut (generasi) tercatat</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="angka"><?= number_format($hidup, 0, ',', '.') ?></div><div class="label">Anggota yang hidup</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat"><div class="angka"><?= $aktif ? esc($aktif[0] . '–' . $aktif[1]) : '–' ?></div><div class="label">Sundut yang aktif</div></div></div>
    </div>
</div>

<div class="container mt-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="judul-bagian"><h2>Kabar Terbaru</h2></div>
            <?php if ($berita === []) : ?>
                <div class="card card-body text-teks-2">Belum ada berita.</div>
            <?php else : ?>
                <div class="row g-3">
                    <?php foreach ($berita as $b) : ?>
                        <div class="col-md-4"><?= view('partials/kartu_berita', ['b' => $b]) ?></div>
                    <?php endforeach ?>
                </div>
                <div class="text-end mt-2"><a href="<?= site_url('berita') ?>" class="small fw-semibold">Semua berita <i class="bi bi-arrow-right"></i></a></div>
            <?php endif ?>
        </div>
        <div class="col-lg-4">
            <div class="judul-bagian"><h2>Agenda</h2></div>
            <div class="card kartu-aksen">
                <div class="card-body py-2">
                    <?php if ($kegiatan === []) : ?>
                        <p class="text-teks-2 small my-2">Belum ada kegiatan terjadwal.</p>
                    <?php endif ?>
                    <?php foreach ($kegiatan as $i => $k) : ?>
                        <?php if ($i > 0) : ?><hr class="my-1"><?php endif ?>
                        <?= view('partials/baris_kegiatan', ['k' => $k]) ?>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="text-end mt-2"><a href="<?= site_url('kegiatan') ?>" class="small fw-semibold">Semua kegiatan <i class="bi bi-arrow-right"></i></a></div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-4">
            <div class="judul-bagian"><h2>Partuturan</h2></div>
            <div class="tutur">
                <div class="ipon-kecil"></div>
                <div class="isi">
                    <div class="label mb-1">Tutur sapa keluarga</div>
                    <p class="ket">Setiap anggota yang terdaftar dapat melihat bagaimana ia memanggil anggota lain, seperti <b class="text-white">tulang</b>, <b class="text-white">namboru</b>,
                        <b class="text-white">amangtua</b>, <b class="text-white">lae</b>, atau <b class="text-white">eda</b>, beserta jalur silsilahnya.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= site_url('hubungan') ?>" class="btn btn-sm btn-utama">Cek partuturan</a>
                        <a href="<?= site_url('partuturan') ?>" class="btn btn-sm btn-garis-putih">Kamus partuturan</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="judul-bagian"><h2>Anggota per Sundut</h2></div>
            <div class="card">
                <div class="card-body">
                    <div class="small text-teks-2 mb-2 d-flex flex-wrap justify-content-between gap-2">
                        <span>
                            <i class="d-inline-block rounded-1 garis-utama" style="width:.7rem;height:.7rem"></i> Garis utama
                            <i class="d-inline-block rounded-1 garis-boru ms-2" style="width:.7rem;height:.7rem"></i> Boru
                        </span>
                        <?php if ($leluhur) : ?><span>Leluhur awal: <b><?= esc($leluhur->gelar_adat ?: $leluhur->nama_lengkap) ?></b></span><?php endif ?>
                    </div>
                    <?php if ($rekap === []) : ?><p class="text-teks-2 mb-0">Belum ada data.</p><?php endif ?>
                    <?php foreach ($rekap as $r) : ?>
                        <a class="row g-2 align-items-center text-decoration-none text-body mb-1" href="<?= site_url('generasi?g=' . $r['generasi_ke']) ?>">
                            <div class="col-3 col-sm-2 small fw-semibold text-nowrap">Sundut <?= $r['generasi_ke'] ?></div>
                            <div class="col">
                                <div class="bar-generasi" title="<?= $r['utama'] ?> garis utama, <?= $r['boru'] ?> boru">
                                    <span class="garis-utama" style="width:<?= round($r['utama'] / $maks * 100, 1) ?>%"></span>
                                    <span class="garis-boru" style="width:<?= round($r['boru'] / $maks * 100, 1) ?>%"></span>
                                </div>
                            </div>
                            <div class="col-3 col-sm-2 small text-end text-teks-2"><?= number_format($r['utama'] + $r['boru'], 0, ',', '.') ?></div>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
