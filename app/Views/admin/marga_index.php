<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Marga<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Marga</h1>
        <a href="<?= site_url('admin/marga/tambah') ?>" class="btn btn-utama"><i class="bi bi-plus-lg"></i> Tambah marga</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Rumpun</th><th class="text-center">Silsilah Pokok</th><th class="text-end">Anggota</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $m) : ?>
                    <tr>
                        <td class="font-monospace"><?= esc($m['kode']) ?></td>
                        <td class="fw-medium"><?= esc($m['nama']) ?></td>
                        <td><?= esc($m['nama_rumpun'] ?? '') ?></td>
                        <td class="text-center">G1–G<?= (int) $m['batas_silsilah_pokok'] ?></td>
                        <td class="text-end"><?= number_format((int) $m['jumlah'], 0, ',', '.') ?></td>
                        <td><?= $m['is_active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/marga/' . $m['id'] . '/ubah') ?>">Ubah</a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
