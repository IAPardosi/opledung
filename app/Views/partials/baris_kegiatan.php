<?php
/** @var array<string, mixed> $k */
$jenis = config('Silsilah')->jenisKegiatan[$k['jenis']] ?? $k['jenis'];
?>
<a href="<?= site_url('kegiatan/' . $k['slug']) ?>" class="d-flex gap-3 align-items-center text-body py-2 text-decoration-none">
    <div class="tanggal-kotak<?= ! empty($gelap) ? ' gelap' : '' ?>">
        <div class="bln"><?= esc(nama_bulan($k['mulai'], true)) ?></div>
        <div class="tgl"><?= sprintf('%02d', (int) substr($k['mulai'], 8, 2)) ?></div>
    </div>
    <div style="min-width: 0">
        <div class="kategori text-utama"><?= esc($jenis) ?><?= $k['status'] === 'batal' ? ' · <span class="text-danger">Dibatalkan</span>' : '' ?></div>
        <div class="fw-semibold"><?= esc($k['judul']) ?></div>
        <div class="small text-teks-2 text-truncate"><?= esc(substr($k['mulai'], 11, 5)) ?> WIB · <?= esc($k['lokasi']) ?></div>
    </div>
</a>
