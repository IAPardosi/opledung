<?php
/**
 * @var \App\Entities\Person $person
 */
$kartu = fn ($o, ?string $ket = null, bool $pusat = false): string => view('partials/kartu_orang', ['o' => $o, 'tutur' => $tutur, 'sayaId' => $sayaId, 'ket' => $ket, 'pusat' => $pusat]);
$baris = static function (string $label, string $isi): string {
    return $isi === '' ? '' : '<div class="mb-4"><div class="label-baris">' . esc($label) . '</div><div class="baris-keluarga">' . $isi . '</div></div>';
};
$diri = $sayaId === $person->id;
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Keluarga Dekat <?= esc($person->nama_lengkap) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <?= view('partials/mode_tampil', ['aktif' => 'keluarga', 'id' => $diri ? null : $person->id]) ?>
        <?php if (! $diri && $sayaId) : ?><a href="<?= site_url('keluarga-dekat') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bullseye"></i> Kembali ke saya</a><?php endif ?>
    </div>
    <p class="eyebrow mb-1">Keluarga dekat · Sundut <?= $person->generasi_ke ?></p>
    <h1 class="h2 mb-1"><?= $diri ? 'Keluarga Anda' : esc($person->nama_lengkap) ?></h1>
    <p class="text-teks-2 mb-4">Dua sundut ke atas dan ke bawah. Ketuk seseorang untuk menjadikannya pusat.<?= $tutur !== [] ? ' Label di bawah nama adalah partuturan Anda kepadanya.' : '' ?></p>

    <div class="card card-body p-4">
        <?= $baris('Ompung · Sundut ' . ($person->generasi_ke - 2), ($ompung ? $kartu($ompung) : '') . implode('', array_map(fn ($ps) => $kartu($ps, 'Pasangan'), $ompungPasangan))) ?>
        <?= $baris('Orang tua & saudaranya · Sundut ' . ($person->generasi_ke - 1),
            ($orangTua ? $kartu($orangTua) : '') . implode('', array_map(fn ($ps) => $kartu($ps, 'Pasangan'), $orangTuaPasangan))
            . implode('', array_map(fn ($s) => $kartu($s), $saudaraOrangTua))) ?>
        <?= $baris('Sundut ' . $person->generasi_ke,
            $kartu($person, null, true) . implode('', array_map(fn ($ps) => $kartu($ps, 'Pasangan'), $pasangan))
            . implode('', array_map(fn ($s) => $kartu($s), $saudara))) ?>
        <?= $baris('Anak · Sundut ' . ($person->generasi_ke + 1), implode('', array_map(fn ($a) => $kartu($a), $anak))) ?>
        <?= $baris('Pahompu · Sundut ' . ($person->generasi_ke + 2) . ($jumlahPahompu > count($pahompu) ? ' (40 dari ' . $jumlahPahompu . ')' : ''), implode('', array_map(fn ($c) => $kartu($c), $pahompu))) ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-gelap" href="<?= site_url('garis/' . $person->id) ?>"><i class="bi bi-signpost-split"></i> Jalur ke Sundut 1</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('anggota/' . $person->id) ?>"><i class="bi bi-person-vcard"></i> Profil</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('silsilah/' . $person->id) ?>"><i class="bi bi-diagram-3"></i> Pohon cabang</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
