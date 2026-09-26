<?php
/**
 * Pemilih mode tampilan silsilah.
 *
 * @var string   $aktif garis|keluarga|sundut|pohon
 * @var int|null $id    orang yang sedang dilihat (opsional)
 */
$id    = $id ?? null;
$modes = [
    'garis'    => ['Jalur saya', 'bi-signpost-split', 'garis' . ($id ? '/' . $id : '')],
    'keluarga' => ['Keluarga dekat', 'bi-people', 'keluarga-dekat' . ($id ? '/' . $id : '')],
    'sundut'   => ['Per sundut', 'bi-list-ol', 'generasi'],
    'pohon'    => ['Pohon cabang', 'bi-diagram-3', 'silsilah' . ($id ? '/' . $id : '')],
];
?>
<nav class="mode-tampil" aria-label="Cara menampilkan silsilah">
    <?php foreach ($modes as $k => [$label, $ikon, $url]) : ?>
        <a href="<?= site_url($url) ?>" class="<?= $aktif === $k ? 'active' : '' ?>" <?= $aktif === $k ? 'aria-current="page"' : '' ?>><i class="bi <?= $ikon ?>"></i> <?= $label ?></a>
    <?php endforeach ?>
</nav>
