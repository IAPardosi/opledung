<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Usulan Data<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Usulan Data</h1>
        <ul class="nav nav-pills">
            <?php foreach (['pending' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $k => $v) : ?>
                <li class="nav-item"><a class="nav-link<?= $status === $k ? ' active' : '' ?>" href="<?= site_url('admin/usulan?status=' . $k) ?>"><?= $v ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Diajukan</th><th>Jenis</th><th>Untuk</th><th class="text-center">Gen.</th><th>Pengusul</th><th>Punguan</th><th>Validasi keluarga</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if ($rows === []) : ?>
                    <tr><td colspan="9" class="text-center text-body-secondary py-4">Tidak ada usulan.</td></tr>
                <?php endif ?>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td class="small"><?= esc(tanggal_indo($r['created_at'])) ?></td>
                        <td><?= esc(UsulanService::JENIS[$r['jenis']] ?? $r['jenis']) ?></td>
                        <td><?= esc($r['nama_lengkap'] ?? '–') ?><div class="small text-body-secondary font-monospace"><?= esc($r['kode_anggota'] ?? '') ?></div></td>
                        <td class="text-center"><?= esc($r['generasi_ke'] ?? '') ?></td>
                        <td><?= esc($r['username']) ?></td>
                        <td class="small"><?= esc($r['nama_punguan'] ?? '–') ?></td>
                        <td class="small text-nowrap"><?= [
                            'benar'     => '<span class="chip chip-hijau"><i class="bi bi-check-circle-fill"></i> Benar</span>',
                            'salah'     => '<span class="chip chip-merah"><i class="bi bi-x-circle-fill"></i> Tidak benar</span>',
                            'menunggu'  => '<span class="chip"><i class="bi bi-hourglass-split"></i> Menunggu</span>',
                            'tidak_ada' => '<span class="chip">Tanpa validator</span>',
                        ][$r['status_keluarga'] ?? ''] ?? '–' ?><?php if ((int) $r['saksi_benar'] > 0) : ?> <span class="small text-success">+<?= (int) $r['saksi_benar'] ?> saksi</span><?php endif ?></td>
                        <td><?= view('partials/status_usulan', ['status' => $r['status']]) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/usulan/' . $r['id']) ?>">Periksa</a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= $pager->links('default', 'default_full') ?></div>
</div>
<?= $this->endSection() ?>
