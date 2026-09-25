<?php
/**
 * Kartu partuturan: panggilan, panggilan balik, dan rantai jalur.
 *
 * @var array<string, mixed>         $hasil
 * @var \App\Entities\Person         $dari
 * @var \App\Entities\Person         $ke
 * @var bool                         $diri  $dari adalah pengguna yang login
 */
$subjek = $diri ? 'Anda' : esc($dari->nama_lengkap);
?>
<div class="tutur">
    <div class="ipon-kecil"></div>
    <div class="isi">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="label"><?= $subjek ?> memanggil <?= esc(mb_strimwidth($ke->nama_lengkap, 0, 34, '…')) ?></div>
                <div class="sebutan"><?= esc($hasil['sebutan']) ?></div>
                <?php if ($hasil['keterangan']) : ?><div class="ket mt-1"><?= esc($hasil['keterangan']) ?></div><?php endif ?>
            </div>
            <div class="col-md-6">
                <div class="label"><?= esc(mb_strimwidth($ke->nama_lengkap, 0, 34, '…')) ?> memanggil <?= $diri ? 'Anda' : esc(mb_strimwidth($dari->nama_lengkap, 0, 34, '…')) ?></div>
                <div class="sebutan" style="font-size:1.5rem"><?= esc($hasil['balik']['sebutan']) ?></div>
            </div>
        </div>
        <?php if (count($hasil['jalur']) > 1) : ?>
            <div class="label mt-4 mb-2">Jalur silsilah</div>
            <div class="rantai">
                <?php foreach ($hasil['jalur'] as $i => $j) : ?>
                    <?php if ($i > 0) : ?><i class="bi bi-chevron-right" style="color:#8f8079"></i><?php endif ?>
                    <?php
                    $kelas = ($hasil['titik_temu']?->id === $j->id) ? ' temu' : '';
                    $kelas .= ($j->id === $dari->id || $j->id === $ke->id) ? ' ujung' : '';
                    ?>
                    <a href="<?= site_url('anggota/' . $j->id) ?>" class="simpul text-decoration-none<?= $kelas ?>">
                        <?= esc(mb_strimwidth($j->nama_lengkap, 0, 26, '…')) ?>
                        <small><?= $j->garis === 'pasangan' ? 'Pasangan' : 'Sundut ' . $j->generasi_ke ?><?= ($hasil['titik_temu']?->id === $j->id) ? ' · titik temu' : '' ?></small>
                    </a>
                <?php endforeach ?>
            </div>
            <div class="ket small mt-2"><?= esc($hasil['rincian']) ?></div>
        <?php endif ?>
    </div>
</div>
