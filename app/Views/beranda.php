<?php
/**
 * @var list<\App\Entities\Person> $jalur
 */
$n        = count($jalur);
$maksPung = max([1, ...array_column($punguan, 'jumlah')]);
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Horas!<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">

    <!-- Hero -->
    <section class="hero-a position-relative pt-4 pb-2">
        <svg class="motif" viewBox="0 0 96 96" aria-hidden="true"><g fill="none" stroke="#a3161e" stroke-width="1.2" stroke-linecap="round"><path d="M30 32a2 2 0 0 1 4 0a4 4 0 0 1-8 0a6 6 0 0 1 12 0a8 8 0 0 1-16 0a10 10 0 0 1 20 0a12 12 0 0 1-24 0"/><path d="M66 64a2 2 0 0 1-4 0a4 4 0 0 1 8 0a6 6 0 0 1-12 0a8 8 0 0 1 16 0a10 10 0 0 1-20 0a12 12 0 0 1 24 0"/><path d="M18 32C18 52 40 44 48 48S78 44 78 64"/></g></svg>
        <div class="row g-4 g-lg-5 align-items-center position-relative">
            <div class="col-lg-7 d-flex flex-column gap-4">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="chip chip-merah">HORAS!</span>
                    <span class="small text-teks-2">Pomparan <?= esc($marga['nama_rumpun'] ?: $marga['nama']) ?><?= $punguanSaya ? ' · ' . esc($punguanSaya['nama']) : '' ?></span>
                </div>
                <h1 class="m-0">Satu marga, satu tarombo, di mana pun kita berada.</h1>
                <p class="lead m-0">Lihat garis keturunan Anda dari Sundut 1 sampai hari ini, ketahui partuturan dengan sesama pomparan, dan ikuti kegiatan punguan.</p>
                <form action="<?= site_url('generasi') ?>" method="get" class="input-kapsul" role="search" style="max-width: 620px">
                    <i class="bi bi-search text-teks-2"></i>
                    <input type="search" name="q" placeholder="Cari nama atau kode anggota, mis. PDS-G12-000345" aria-label="Cari anggota">
                    <button class="btn btn-gelap px-4" type="submit">Cari</button>
                </form>
                <div class="d-flex flex-wrap gap-4">
                    <div class="stat"><div class="angka"><?= number_format($total, 0, ',', '.') ?></div><div class="label">anggota tercatat</div></div>
                    <div class="stat"><div class="angka"><?= count($rekap) ?></div><div class="label">sundut</div></div>
                    <div class="stat"><div class="angka"><?= $aktif ? esc($aktif[0] . '–' . $aktif[1]) : '–' ?></div><div class="label">sundut aktif</div></div>
                    <div class="stat"><div class="angka"><?= count($punguan) ?></div><div class="label">punguan</div></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="position-relative">
                    <div class="card p-4 kartu-jalur" style="border-radius: 32px; box-shadow: 0 30px 60px rgba(22,17,15,.08)">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="eyebrow">Garis keturunan <?= $jalurSaya ? 'saya' : '' ?></div>
                                <div class="h5 mb-0 mt-1"><?= $jalurSaya ? esc(end($jalur)->nama_lengkap) . ' · Sundut ' . end($jalur)->generasi_ke : 'Dari Sundut 1 sampai Anda' ?></div>
                            </div>
                            <?php if ($jalurSaya) : ?><span class="chip chip-hijau">Sah</span><?php endif ?>
                        </div>
                        <div class="linimasa mb-3">
                            <?php foreach ($jalur as $i => $j) : ?>
                                <?php if ($n > 5 && $i >= 3 && $i < $n - 2) : ?>
                                    <?php if ($i === 3) : ?>
                                        <div class="simpul"><span class="nomor lipat">+<?= $n - 5 ?></span><div class="isi"><a class="chip" href="<?= site_url('garis') ?>">Tampilkan Sundut <?= $jalur[3]->generasi_ke ?>–<?= $jalur[$n - 3]->generasi_ke ?></a></div></div>
                                        <div class="sambung"></div>
                                    <?php endif ?>
                                    <?php continue ?>
                                <?php endif ?>
                                <?php $ini = $jalurSaya && $i === $n - 1; ?>
                                <div class="simpul"><span class="nomor<?= $ini ? ' saya' : '' ?>"><?= $j->generasi_ke ?></span>
                                    <div class="isi"><div class="nama"><?= $ini ? 'Anda' : esc($j->gelar_adat ?: $j->nama_lengkap) ?></div>
                                        <div class="ket"><?= esc($ini ? ($saudara[$j->id] ?? 0) . ' saudara' : ($i === 0 ? 'Leluhur awal marga ' . $marga['nama'] : ($i === $n - 2 && $jalurSaya ? 'Ayah' : lahir_wafat($j)))) ?></div></div></div>
                                <?php if ($i < $n - 1) : ?><div class="sambung<?= $jalurSaya && $i === $n - 2 ? ' merah' : '' ?>"></div><?php endif ?>
                            <?php endforeach ?>
                            <?php if (! $jalurSaya) : ?>
                                <div class="sambung putus"></div>
                                <div class="simpul"><span class="nomor saya">?</span><div class="isi"><div class="nama">Anda</div><div class="ket">Daftarkan keluarga untuk melihat jalur Anda</div></div></div>
                            <?php endif ?>
                        </div>
                        <a href="<?= site_url($jalurSaya ? 'garis' : 'register') ?>" class="d-flex justify-content-between align-items-center px-3 py-2 rounded-4 text-body fw-semibold" style="background: var(--latar)">
                            <?= $jalurSaya ? 'Lihat garis lengkap' : 'Daftarkan keluarga saya' ?> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <?php if ($tutur) : ?>
                        <div class="chip-partuturan">
                            <div class="small fw-bold" style="letter-spacing:.12em;color:var(--merah-terang)">ANDA MEMANGGIL</div>
                            <div class="h4 m-0"><?= esc($tutur['sebutan']) ?></div>
                            <div class="small" style="color:#cdbfb3"><?= esc($tutur['nama']) ?> · Sundut <?= $tutur['generasi'] ?></div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>

    <div class="ipon my-5"></div>

    <!-- Bento -->
    <section class="mb-5">
        <div class="judul-bagian"><h2>Jelajahi tarombo</h2><span class="small text-teks-2 d-none d-md-inline">Pilih cara melihat yang paling cocok</span></div>
        <div class="bento">
            <a href="<?= site_url('silsilah') ?>" class="besar kartu-gelap position-relative overflow-hidden">
                <svg viewBox="0 0 460 300" class="ilustrasi-pohon" aria-hidden="true">
                    <g fill="none" stroke="#5a4a44" stroke-width="2"><path d="M60 150C120 150 120 70 180 70M60 150C120 150 120 230 180 230M240 70C300 70 300 30 360 30M240 70C300 70 300 110 360 110M240 230C300 230 300 190 360 190M240 230C300 230 300 270 360 270"/></g>
                    <rect x="10" y="132" width="100" height="36" rx="12" fill="#a3161e"/><rect x="180" y="52" width="60" height="36" rx="12" fill="#2b2220"/><rect x="180" y="212" width="60" height="36" rx="12" fill="#2b2220"/><rect x="360" y="12" width="70" height="36" rx="12" fill="#3a2e2a"/><rect x="360" y="92" width="70" height="36" rx="12" fill="#3a2e2a"/><rect x="360" y="172" width="70" height="36" rx="12" fill="#3a2e2a"/><rect x="360" y="252" width="70" height="36" rx="12" fill="#3a2e2a"/>
                </svg>
                <div class="position-relative">
                    <div class="eyebrow" style="color: var(--merah-terang)">Pohon silsilah</div>
                    <div class="h2 mt-2 mb-0" style="max-width: 320px">Buka cabang demi cabang, tanpa tersesat</div>
                </div>
                <div class="position-relative d-flex flex-wrap gap-2 mt-4">
                    <span class="chip">Jalur saya</span>
                    <span class="chip chip-gelap" style="border-color:#5a4a44">Keluarga dekat</span>
                    <span class="chip chip-gelap" style="border-color:#5a4a44">Per sundut</span>
                    <span class="chip chip-gelap" style="border-color:#5a4a44">Pohon cabang</span>
                </div>
            </a>
            <a href="<?= site_url('hubungan') ?>" class="kartu-merah">
                <div class="eyebrow" style="color:#fbd5d7">Partuturan</div>
                <div><div class="h1 m-0" style="line-height:1">Tulang?<br>Lae?</div><div class="small mt-2" style="color:#fbd5d7">Cek panggilan Anda ke siapa pun</div></div>
            </a>
            <a href="<?= site_url('kenali-marga') ?>" class="card text-body">
                <div class="eyebrow">Kenali marga</div>
                <div><div class="h4 m-0">Dari leluhur sebelum <?= esc($marga['nama']) ?> sampai Anda</div><div class="small text-teks-2 mt-2">Kisah, sundut awal, dan bona pasogit</div></div>
            </a>
            <div class="lebar card flex-row gap-4 align-items-stretch">
                <div class="d-flex flex-column justify-content-between gap-2" style="flex: 1 1 180px">
                    <div class="eyebrow">Sebaran pomparan</div>
                    <div class="h4 m-0">Satu database, dari Medan sampai luar negeri</div>
                </div>
                <div class="d-flex flex-column justify-content-center gap-3 small" style="flex: 1 1 220px; min-width: 0">
                    <?php foreach (array_slice($punguan, 0, 4) as $p) : ?>
                        <a href="<?= site_url('punguan/' . $p['slug']) ?>" class="d-grid align-items-center gap-2 text-body" style="grid-template-columns: minmax(0, 110px) 1fr 48px">
                            <span class="fw-semibold text-truncate"><?= esc(preg_replace('/^Punguan\s+/i', '', $p['nama'])) ?></span>
                            <span class="bar-generasi"><span class="garis-utama" style="width: <?= round($p['jumlah'] / $maksPung * 100) ?>%"></span></span>
                            <span class="text-end" style="font-variant-numeric: tabular-nums"><?= number_format($p['jumlah'], 0, ',', '.') ?></span>
                        </a>
                    <?php endforeach ?>
                    <a href="<?= site_url('punguan') ?>" class="small fw-semibold">Semua punguan</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Agenda & kabar -->
    <section class="row g-4 mb-5">
        <div class="col-lg-5">
            <div class="judul-bagian"><h2 class="h3">Agenda <?= esc($punguanSaya ? preg_replace('/^Punguan\s+/i', '', $punguanSaya['nama']) : '') ?></h2><a href="<?= site_url('kegiatan') ?>" class="small fw-semibold">Semua</a></div>
            <div class="card p-2">
                <?php if ($kegiatan === []) : ?><p class="text-teks-2 small m-3">Belum ada kegiatan terjadwal.</p><?php endif ?>
                <?php foreach ($kegiatan as $i => $k) : ?>
                    <?php if ($i > 0) : ?><hr class="my-0 mx-3" style="border-color: var(--garis-halus)"><?php endif ?>
                    <div class="px-2"><?= view('partials/baris_kegiatan', ['k' => $k, 'gelap' => $i === 0]) ?></div>
                <?php endforeach ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="judul-bagian"><h2 class="h3">Kabar terbaru</h2><a href="<?= site_url('berita') ?>" class="small fw-semibold">Semua kabar</a></div>
            <?php if ($berita === []) : ?><div class="card card-body text-teks-2">Belum ada kabar.</div><?php endif ?>
            <div class="row g-3">
                <?php foreach ($berita as $b) : ?><div class="col-md-6"><?= view('partials/kartu_berita', ['b' => $b]) ?></div><?php endforeach ?>
            </div>
        </div>
    </section>

    <!-- Ajakan kepala keluarga -->
    <?php if (! $jalurSaya) : ?>
    <section class="kartu-lembut p-4 p-lg-5 d-flex flex-wrap gap-4 justify-content-between align-items-center" style="border-radius: 32px">
        <div style="max-width: 720px">
            <div class="h3 mb-2">Daftarkan keluarga Anda sekali, untuk semua keturunan.</div>
            <div class="text-teks-2">Kepala keluarga mencatat istri dan anak-anak. Anak yang sudah menikah cukup menekan "Ini saya", silsilahnya otomatis sama.</div>
        </div>
        <a href="<?= site_url('register') ?>" class="btn btn-utama btn-lg">Daftar sebagai kepala keluarga</a>
    </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
