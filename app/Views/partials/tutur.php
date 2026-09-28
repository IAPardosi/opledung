<?php
/**
 * Kartu tutur: panggilan dua arah, penjelasan, jalur silsilah, dan posisi Dalihan Na Tolu.
 *
 * @var array<string, mixed>         $hasil
 * @var \App\Entities\Person         $dari
 * @var \App\Entities\Person         $ke
 * @var bool                         $diri  $dari adalah pengguna yang login
 */
use App\Services\PartuturanService;

$subjek = $diri ? 'Anda' : esc($dari->nama_lengkap);
$namaKe = esc(mb_strimwidth($ke->nama_lengkap, 0, 34, '…'));
$dnt    = $hasil['dalihan'] ?? null;
?>
<div class="tutur kartu-tutur">
    <div class="isi">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="label"><?= $subjek ?> memanggil <?= $namaKe ?></div>
                <div class="sebutan sebutan-besar"><?= esc($hasil['sebutan']) ?></div>
                <div class="mt-2"><?= $namaKe ?> memanggil <?= $diri ? 'Anda' : esc($dari->nama_lengkap) ?> <b class="text-emas-terang"><?= esc($hasil['balik']['sebutan']) ?></b></div>
                <?php if ($hasil['keterangan']) : ?><p class="ket mt-3 mb-0"><?= esc($hasil['keterangan']) ?></p><?php endif ?>
            </div>
            <div class="col-lg-4">
                <?php if (count($hasil['jalur']) > 1) : ?>
                    <div class="label mb-2">Jalur silsilah</div>
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
            <div class="col-lg-3">
                <div class="kotak-dnt">
                    <div class="label mb-2">Dalihan Na Tolu</div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach (PartuturanService::DALIHAN as $k => $d) : ?>
                            <span class="chip-dnt<?= ($dnt['kunci'] ?? '') === $k ? ' aktif' : '' ?>"><?= esc($d['nama']) ?></span>
                        <?php endforeach ?>
                    </div>
                    <?php if ($dnt) : ?>
                        <p class="small mb-1"><b class="text-emas-terang"><?= esc($dnt['nama']) ?></b> bagi <?= $diri ? 'Anda' : esc($dari->nama_lengkap) ?>. <?= esc($dnt['arti']) ?></p>
                        <p class="small mb-0 fst-italic ket"><?= esc($dnt['semboyan']) ?></p>
                    <?php else : ?>
                        <p class="small mb-0 ket">Posisi adat untuk hubungan ini belum dapat ditentukan dari silsilah; tanyakan kepada Ketua Adat.</p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
