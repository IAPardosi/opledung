<?php
/**
 * Mapping keturunan: Fokus (garis lurus) / Keluarga / Lengkap.
 *
 * @var array<string, mixed>|null $peta hasil SilsilahQuery::mapping()
 * @var string                    $mode
 * @var bool                      $diri
 */
$modeLabel = [
    'fokus'    => ['Fokus saya', 'bi-bullseye', 'Hanya garis lurus dari leluhur sampai orang ini. Saudara di setiap sundut dilipat (+N).'],
    'keluarga' => ['Keluarga', 'bi-people', 'Garis lurus beserta saudara di setiap sundut, anak, dan cucu.'],
    'lengkap'  => ['Lengkap', 'bi-diagram-3', 'Semua cabang dari sundut awal sampai sundut orang ini.'],
];
$target = $peta['target'] ?? null;
$mulai  = $peta['mulai'] ?? null;
$url    = static fn (array $q = []): string => site_url('mapping' . ($target && ! $diri ? '/' . $target->id : '')) . '?' . http_build_query(array_filter([
    'mode' => $q['mode'] ?? $mode,
    'dari' => array_key_exists('dari', $q) ? $q['dari'] : ($mulai && $mulai->generasi_ke > 1 ? $mulai->generasi_ke : null),
]));
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Mapping Keturunan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid px-lg-4">
    <div class="mb-3"><?= view('partials/mode_tampil', ['aktif' => 'mapping', 'id' => $target && ! $diri ? $target->id : null]) ?></div>

    <?php if ($peta === null) : ?>
        <div class="card card-body p-4 p-lg-5 mx-auto" style="max-width: 720px">
            <p class="eyebrow mb-1">Mapping keturunan</p>
            <h1 class="h3">Petakan garis keturunan dari Sundut 1</h1>
            <p class="text-teks-2">Pilih anggota untuk melihat jalurnya dari leluhur awal <?= esc($marga['nama']) ?>. <?php if (! auth()->loggedIn()) : ?><a href="<?= site_url('login') ?>">Masuk</a> agar mapping langsung menampilkan jalur Anda.<?php elseif (auth()->user()->person_id === null) : ?>Akun Anda belum tertaut ke silsilah, jadi pilih nama terlebih dahulu.<?php endif ?></p>
            <div class="position-relative">
                <input type="search" id="cariPohon" class="form-control input-kapsul" placeholder="Ketik nama anggota…" autocomplete="off" aria-label="Cari anggota">
                <div id="hasilCari" class="list-group position-absolute w-100 shadow-sm" style="z-index:10"></div>
            </div>
        </div>
    <?php else : ?>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <p class="eyebrow mb-1">Mapping keturunan</p>
                <h1 class="h3 mb-1"><?= $diri ? 'Jalur Anda' : esc($target->nama_lengkap) ?> <span class="text-teks-3 fw-normal">· Sundut <?= $target->generasi_ke ?></span></h1>
                <p class="text-teks-2 mb-0 small">
                    Dari <b>G<?= $mulai->generasi_ke ?> <?= esc($mulai->gelar_adat ?: $mulai->nama_lengkap) ?></b> sampai <b><?= $diri ? 'Anda' : esc($target->nama_lengkap) ?></b>
                    · <?= $target->generasi_ke - $mulai->generasi_ke + 1 ?> sundut · <?= number_format($peta['jumlah'], 0, ',', '.') ?> orang ditampilkan
                </p>
            </div>
            <div class="position-relative" style="min-width:260px">
                <input type="search" id="cariPohon" class="form-control input-kapsul" placeholder="Mapping anggota lain…" autocomplete="off" aria-label="Cari anggota">
                <div id="hasilCari" class="list-group position-absolute w-100 shadow-sm" style="z-index:10"></div>
            </div>
        </div>

        <div class="card card-body py-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="mode-tampil" role="group" aria-label="Kelengkapan mapping">
                    <?php foreach ($modeLabel as $k => [$label, $ikon]) : ?>
                        <a href="<?= $url(['mode' => $k]) ?>" class="<?= $mode === $k ? 'active' : '' ?>" data-mode="<?= $k ?>" <?= $mode === $k ? 'aria-current="true"' : '' ?>><i class="bi <?= $ikon ?>"></i> <?= $label ?></a>
                    <?php endforeach ?>
                </div>
                <form method="get" class="d-flex align-items-center gap-2" id="formDari">
                    <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                    <label for="dari" class="small fw-semibold text-teks-2 text-nowrap">Mulai dari</label>
                    <select name="dari" id="dari" class="form-select form-select-sm" style="width:auto">
                        <?php foreach ($peta['jalur'] as $j) : ?>
                            <option value="<?= $j->generasi_ke ?>" <?= $j->id === $mulai->id ? 'selected' : '' ?>>Sundut <?= $j->generasi_ke ?> · <?= esc(mb_strimwidth($j->gelar_adat ?: $j->nama_lengkap, 0, 26, '…')) ?></option>
                        <?php endforeach ?>
                    </select>
                    <noscript><button class="btn btn-sm btn-gelap">Terapkan</button></noscript>
                </form>
                <p class="small text-teks-2 mb-0 flex-grow-1"><i class="bi bi-info-circle"></i> <?= $modeLabel[$mode][2] ?></p>
            </div>
            <?php if ($peta['dipangkas']) : ?>
                <div class="alert alert-warning small mt-3 mb-0 py-2">Cabang terlalu banyak untuk ditampilkan sekaligus, jadi sundut terbawah dilipat. Klik tanda <b>+</b> untuk membuka, atau pilih sundut awal yang lebih dekat.</div>
            <?php endif ?>
        </div>

        <div class="row g-3">
            <div class="col-lg-9">
                <div class="pohon-wrap">
                    <svg id="pohon" role="img" aria-label="Mapping keturunan"></svg>
                    <div class="pohon-kontrol">
                        <button class="btn btn-light btn-sm border" id="zoomMasuk" title="Perbesar"><i class="bi bi-plus-lg"></i></button>
                        <button class="btn btn-light btn-sm border" id="zoomKeluar" title="Perkecil"><i class="bi bi-dash-lg"></i></button>
                    <button class="btn btn-light btn-sm border" id="putarArah" title="Ubah arah: mendatar / menurun"><i class="bi bi-arrow-repeat"></i></button>
                        <button class="btn btn-light btn-sm border" id="zoomReset" title="Kembali ke <?= $diri ? 'saya' : 'orang ini' ?>"><i class="bi bi-crosshair"></i></button>
                        <button class="btn btn-light btn-sm border" id="zoomSemua" title="Lihat seluruhnya"><i class="bi bi-arrows-fullscreen"></i></button>
                    </div>
                    <div class="pohon-legenda">
                        <i class="garis-jalur"></i>Jalur <?= $diri ? 'saya' : 'terpilih' ?> <i class="garis-utama"></i>Anak <i class="garis-boru"></i>Boru <i class="garis-pasangan"></i>Istri/suami <span class="ms-2 fw-bold">+N</span> belum dibuka
                    </div>
                </div>
                <p class="small text-body-secondary mt-2 mb-0"><i class="bi bi-hand-index"></i> Klik kartu untuk ringkasan. Klik <b>+N</b> untuk membuka saudara atau anak yang dilipat. Geser dan gulir untuk menjelajah.</p>
            </div>
            <div class="col-lg-3">
                <div class="card" id="panelOrang">
                    <div class="card-body text-body-secondary small">Pilih seseorang di mapping untuk melihat ringkasannya.</div>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/vendor/d3/d3.min.js') ?>"></script>
<script>
    window.SILSILAH = {
        data: <?= $peta === null ? 'null' : json_encode($peta['akar'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        baseUrl: <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>,
        login: <?= auth()->loggedIn() ? 'true' : 'false' ?>,
        label: {utama: 'Garis utama', boru: 'Boru', anak_boru: 'Anak boru'},
        mapping: {mode: <?= json_encode($mode) ?>, target: <?= $target ? $target->id : 'null' ?>},
        urlCari: 'mapping/',
    };
    document.getElementById('dari')?.addEventListener('change', (e) => e.target.form.submit());
</script>
<script src="<?= base_url('assets/js/pohon.js') ?>"></script>
<?= $this->endSection() ?>
