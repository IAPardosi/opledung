<?php use App\Models\PunguanModel; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kelola Punguan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="judul-bagian"><h1 class="h3">Punguan</h1><a href="<?= site_url('admin/punguan/tambah') ?>" class="btn btn-utama"><i class="bi bi-plus-lg"></i> Tambah punguan</a></div>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama</th><th>Tingkat</th><th>Cakupan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $p) : ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($p['nama']) ?></td>
                        <td><?= esc(PunguanModel::TINGKAT[$p['tingkat']]) ?></td>
                        <td class="small"><?= esc($p['negara'] ?? $p['wilayah_kode'] ?? '–') ?></td>
                        <td><?= $p['is_active'] ? '<span class="chip chip-hijau">Aktif</span>' : '<span class="chip">Nonaktif</span>' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/punguan/' . $p['id'] . '/ubah') ?>">Ubah</a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-teks-2 mt-3">Penatua punguan ditetapkan di menu <a href="<?= site_url('admin/pengguna') ?>">Pengguna & lingkup</a> (role Penatua Punguan).</p>
</div>
<?= $this->endSection() ?>
