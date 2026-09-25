<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Pohon Silsilah<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Pohon Silsilah <?= esc($marga['nama']) ?></h1>
            <?php if ($jalur !== []) : ?>
                <nav class="jalur" aria-label="Jalur leluhur">
                    <?php foreach ($jalur as $i => $p) : ?>
                        <?php if ($i > 0) : ?><span class="panah"><i class="bi bi-chevron-right"></i></span><?php endif ?>
                        <?php if ($p->id === $akar->id) : ?>
                            <span class="aktif">G<?= $p->generasi_ke ?> · <?= esc($p->nama_lengkap) ?></span>
                        <?php else : ?>
                            <a href="<?= site_url('silsilah/' . $p->id) ?>" title="<?= esc($p->nama_lengkap) ?>">G<?= $p->generasi_ke ?> · <?= esc(mb_strimwidth($p->nama_lengkap, 0, 22, '…')) ?></a>
                        <?php endif ?>
                    <?php endforeach ?>
                </nav>
            <?php endif ?>
        </div>
        <div class="position-relative" style="min-width:280px">
            <input type="search" id="cariPohon" class="form-control" placeholder="Cari nama untuk dibuka di pohon…" autocomplete="off" aria-label="Cari anggota">
            <div id="hasilCari" class="list-group position-absolute w-100 shadow-sm" style="z-index:10"></div>
        </div>
    </div>

    <?php if ($data === null) : ?>
        <div class="card card-body text-center py-5 text-body-secondary">
            <i class="bi bi-diagram-3 fs-1"></i>
            <p class="mt-2 mb-0">Leluhur awal (Generasi 1) belum ditetapkan oleh Ketua Adat.</p>
        </div>
    <?php else : ?>
    <div class="row g-3">
        <div class="col-lg-9">
            <div class="pohon-wrap">
                <svg id="pohon" role="img" aria-label="Pohon silsilah"></svg>
                <div class="pohon-kontrol">
                    <button class="btn btn-light btn-sm border" id="zoomMasuk" title="Perbesar"><i class="bi bi-plus-lg"></i></button>
                    <button class="btn btn-light btn-sm border" id="zoomKeluar" title="Perkecil"><i class="bi bi-dash-lg"></i></button>
                    <button class="btn btn-light btn-sm border" id="zoomReset" title="Posisi awal"><i class="bi bi-arrows-angle-contract"></i></button>
                </div>
                <div class="pohon-legenda">
                    <i class="garis-utama"></i>Garis utama <i class="garis-boru"></i>Boru <i class="garis-anak_boru"></i>Anak boru
                </div>
            </div>
            <p class="small text-body-secondary mt-2 mb-0"><i class="bi bi-info-circle"></i> Klik kartu untuk melihat ringkasan. Klik tanda <b>+</b> untuk membuka generasi berikutnya. Geser dan gulir untuk menjelajah.</p>
        </div>
        <div class="col-lg-3">
            <div class="card" id="panelOrang">
                <div class="card-body text-body-secondary small">Pilih seseorang di pohon untuk melihat ringkasannya.</div>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<?php if ($data !== null) : ?>
<script src="<?= base_url('assets/vendor/d3/d3.min.js') ?>"></script>
<script>
    window.SILSILAH = {
        data: <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        baseUrl: <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>,
        login: <?= auth()->loggedIn() ? 'true' : 'false' ?>,
        label: {utama: 'Garis utama', boru: 'Boru', anak_boru: 'Anak boru'},
    };
</script>
<script src="<?= base_url('assets/js/pohon.js') ?>"></script>
<?php endif ?>
<?= $this->endSection() ?>
