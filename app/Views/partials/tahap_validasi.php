<?php
/**
 * Status dua lapis validasi sebuah usulan.
 *
 * @var array<string, mixed> $usulan
 */
$sk = $usulan['status_keluarga'] ?? null;
$lapis1 = match ($sk) {
    'benar'     => ['chip-hijau', 'bi-check-circle-fill', 'Keluarga membenarkan'],
    'salah'     => ['chip-merah', 'bi-x-circle-fill', 'Keluarga menyatakan tidak benar'],
    'tidak_ada' => ['', 'bi-dash-circle', 'Tanpa validator keluarga'],
    'menunggu'  => ['', 'bi-hourglass-split', 'Menunggu validator keluarga'],
    default     => ['', 'bi-dash-circle', 'Tidak diperlukan'],
};
$lapis2 = match ($usulan['status']) {
    'disetujui' => ['chip-hijau', 'bi-check-circle-fill', 'Disahkan'],
    'ditolak'   => ['chip-merah', 'bi-x-circle-fill', 'Ditolak'],
    default     => ['', 'bi-hourglass-split', 'Menunggu pengesahan'],
};
?>
<div class="d-flex flex-wrap gap-2 align-items-center">
    <span class="chip <?= $lapis1[0] ?>"><i class="bi <?= $lapis1[1] ?>"></i> 1 · <?= $lapis1[2] ?></span>
    <i class="bi bi-chevron-right text-teks-2"></i>
    <span class="chip <?= $lapis2[0] ?>"><i class="bi <?= $lapis2[1] ?>"></i> 2 · <?= $lapis2[2] ?></span>
</div>
