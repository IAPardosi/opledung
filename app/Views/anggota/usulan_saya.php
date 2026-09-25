<?php use App\Services\UsulanService; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Usulan Saya<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <h1 class="h3 mb-3">Usulan Saya</h1>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Untuk</th><th>Status</th><th>Catatan verifikator</th></tr></thead>
                <tbody>
                <?php if ($rows === []) : ?>
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">Belum ada usulan.</td></tr>
                <?php endif ?>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td class="small"><?= esc(tanggal_indo($r['created_at'])) ?></td>
                        <td><?= esc(UsulanService::JENIS[$r['jenis']] ?? $r['jenis']) ?></td>
                        <td><?= $r['person_id'] ? '<a href="' . site_url('anggota/' . $r['person_id']) . '">' . esc($r['nama_lengkap']) . '</a>' : '–' ?></td>
                        <td><?= view('partials/status_usulan', ['status' => $r['status']]) ?></td>
                        <td class="small"><?= esc($r['catatan_verifikator'] ?? '') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
