<?php
$batas = (int) ($marga['batas_silsilah_pokok'] ?? 10);
$total = array_sum(array_map(static fn ($r) => $r['utama'] + $r['boru'], $rekap));
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kenali Marga <?= esc($marga['nama']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <section class="hero-a position-relative py-4 mb-4">
        <p class="eyebrow mb-2">Kenali marga</p>
        <h1 class="mb-3"><?= esc($marga['nama']) ?>: dari leluhur sampai Anda.</h1>
        <p class="lead mb-4">
            <?php if ($marga['nama_rumpun']) : ?>Pomparan <?= esc($marga['nama_rumpun']) ?>. <?php endif ?>
            <?php if ($marga['asal_kampung']) : ?>Bona pasogit di <?= esc($marga['asal_kampung']) ?>. <?php endif ?>
            Kenali urutan sundut, kisah para leluhur, dan tempat Anda di dalamnya.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url($tertaut ? 'garis' : 'register') ?>" class="btn btn-utama btn-lg"><i class="bi bi-signpost-split"></i> <?= $tertaut ? 'Lihat jalur saya' : 'Daftar untuk melihat jalur Anda' ?></a>
            <a href="<?= site_url('silsilah') ?>" class="btn btn-putih btn-lg"><i class="bi bi-diagram-3"></i> Pohon silsilah</a>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="card card-body p-4 h-100">
                <h2 class="h4 mb-3">Kisah marga</h2>
                <?php if ($marga['sejarah']) : ?>
                    <div class="isi-artikel"><?= format_isi($marga['sejarah']) ?></div>
                <?php else : ?>
                    <p class="text-teks-2 mb-0">Kisah marga akan dituliskan oleh Ketua Adat.</p>
                <?php endif ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-body p-4 h-100">
                <h2 class="h4 mb-1">Urutan besar</h2>
                <p class="small text-teks-2">Dari leluhur sebelum marga, ke Silsilah Pokok, sampai sundut yang aktif.</p>
                <div class="linimasa">
                    <?php if ($praMarga !== []) : ?>
                        <?php foreach ($praMarga as $pm) : ?>
                            <div class="simpul"><span class="nomor pra"><i class="bi bi-dot"></i></span><div class="isi"><div class="nama"><?= esc($pm['nama']) ?></div><?php if (! empty($pm['keterangan'])) : ?><div class="ket"><?= esc($pm['keterangan']) ?></div><?php endif ?></div></div>
                            <div class="sambung putus"></div>
                        <?php endforeach ?>
                    <?php endif ?>
                    <?php foreach (($sundut[1] ?? []) as $g1) : ?>
                        <div class="simpul"><span class="nomor">1</span><div class="isi"><div class="nama"><?= esc($g1->gelar_adat ?: $g1->nama_lengkap) ?></div><div class="ket">Leluhur awal marga <?= esc($marga['nama']) ?></div></div></div>
                        <div class="sambung"></div>
                    <?php endforeach ?>
                    <div class="simpul"><span class="nomor lipat"><?= $batas ?></span><div class="isi"><div class="nama">Silsilah Pokok (Sundut 1–<?= $batas ?>)</div><div class="ket">Diisi dan disahkan Ketua Adat</div></div></div>
                    <div class="sambung merah"></div>
                    <div class="simpul"><span class="nomor saya"><?= count($rekap) ?></span><div class="isi"><div class="nama">Sundut <?= $batas + 1 ?>–<?= count($rekap) ?></div><div class="ket"><?= number_format($total, 0, ',', '.') ?> anggota tercatat · didaftarkan kepala keluarga</div></div></div>
                </div>
                <?php if ($praMarga !== []) : ?><p class="small text-teks-2 mt-3 mb-0"><i class="bi bi-info-circle"></i> Leluhur sebelum marga dicatat sebagai informasi sejarah dan tidak dihitung sebagai sundut.</p><?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($sundut !== []) : ?>
    <div class="judul-bagian"><h2>Sundut-sundut awal</h2><span class="small text-teks-2">Garis utama Silsilah Pokok</span></div>
    <?php foreach ($sundut as $g => $orang) : ?>
        <?php if ($orang === []) : continue; endif ?>
        <div class="label-baris">Sundut <?= $g ?></div>
        <div class="row g-3 mb-4">
            <?php foreach ($orang as $o) : ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= site_url('silsilah/' . $o->id) ?>" class="card card-body h-100 text-body">
                        <div class="d-flex gap-3 align-items-center mb-2">
                            <span class="avatar avatar-sm"><?= esc(inisial($o->nama_lengkap)) ?></span>
                            <div><div class="fw-bold"><?= esc($o->gelar_adat ?: $o->nama_lengkap) ?></div><div class="small text-teks-2"><?= esc($o->gelar_adat ? $o->nama_lengkap : lahir_wafat($o)) ?></div></div>
                        </div>
                        <div class="small text-teks-2"><?= esc($o->biografi ? mb_strimwidth($o->biografi, 0, 160, '…') : 'Kisah belum dituliskan.') ?></div>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
