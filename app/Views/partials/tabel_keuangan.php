<?php
/**
 * Tabel riwayat keuangan seorang member punguan.
 *
 * @var list<array<string, mixed>> $riwayat
 */
use App\Services\KeuanganService;

$chip = [
    'sah'      => '<span class="chip chip-hijau"><i class="bi bi-check-circle-fill"></i> Sah</span>',
    'menunggu' => '<span class="chip"><i class="bi bi-hourglass-split"></i> Menunggu validasi</span>',
    'ditolak'  => '<span class="chip chip-merah"><i class="bi bi-x-circle-fill"></i> Ditolak</span>',
];
$total = array_sum(array_map(static fn ($r) => $r['status'] === 'sah' ? (int) $r['nominal'] : 0, $riwayat));
?>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Tanggal</th><th>Kategori</th><th>Periode</th><th class="text-end">Nominal</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($riwayat === []) : ?><tr><td colspan="5" class="text-center text-teks-2 py-4">Belum ada catatan keuangan.</td></tr><?php endif ?>
            <?php foreach ($riwayat as $r) : ?>
                <tr>
                    <td class="small text-nowrap"><?= esc(tanggal_indo($r['tanggal'])) ?></td>
                    <td><?= esc($r['kategori']) ?><?php if ($r['keterangan']) : ?><div class="small text-teks-3"><?= esc($r['keterangan']) ?></div><?php endif ?></td>
                    <td class="small"><?= esc(KeuanganService::labelPeriode($r['periode'])) ?></td>
                    <td class="text-end text-nowrap"><?= rupiah($r['nominal']) ?></td>
                    <td><?= $chip[$r['status']] ?><?php if ($r['status'] === 'ditolak' && $r['alasan_tolak']) : ?><div class="small text-teks-3"><?= esc($r['alasan_tolak']) ?></div><?php endif ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
            <?php if ($riwayat !== []) : ?>
                <tfoot><tr><th colspan="3">Total sah</th><th class="text-end text-nowrap"><?= rupiah($total) ?></th><th></th></tr></tfoot>
            <?php endif ?>
        </table>
    </div>
</div>
