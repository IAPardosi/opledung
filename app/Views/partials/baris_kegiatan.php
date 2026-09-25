<?php
/** @var array<string, mixed> $k */
$jenis = config('Silsilah')->jenisKegiatan[$k['jenis']] ?? $k['jenis'];
?>
<a href="<?= site_url('kegiatan/' . $k['slug']) ?>" class="d-flex gap-3 align-items-start text-decoration-none text-body py-2">
    <div class="tanggal-kotak">
        <div class="bln"><?= esc(nama_bulan($k['mulai'], true)) ?></div>
        <div class="tgl"><?= (int) substr($k['mulai'], 8, 2) ?></div>
    </div>
    <div>
        <div class="kategori text-utama"><?= esc($jenis) ?><?= $k['status'] === 'batal' ? ' · <span class="text-danger">Dibatalkan</span>' : '' ?></div>
        <div class="fw-semibold"><?= esc($k['judul']) ?></div>
        <div class="small text-teks-2"><i class="bi bi-clock"></i> <?= esc(substr($k['mulai'], 11, 5)) ?> WIB · <i class="bi bi-geo-alt"></i> <?= esc($k['lokasi']) ?></div>
    </div>
</a>
