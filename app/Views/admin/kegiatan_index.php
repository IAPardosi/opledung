<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kelola Kegiatan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Kelola Kegiatan</h1>
        <a href="<?= site_url('admin/kegiatan/tambah') ?>" class="btn btn-utama"><i class="bi bi-plus-lg"></i> Tambah kegiatan</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Waktu</th><th>Judul</th><th>Jenis</th><th>Lokasi</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if ($rows === []) : ?><tr><td colspan="6" class="text-center text-teks-2 py-4">Belum ada kegiatan.</td></tr><?php endif ?>
                <?php foreach ($rows as $k) : ?>
                    <tr>
                        <td class="small text-nowrap"><?= esc(tanggal_indo($k['mulai'])) ?><br><?= esc(substr($k['mulai'], 11, 5)) ?> WIB</td>
                        <td class="fw-medium"><?= esc($k['judul']) ?></td>
                        <td class="small"><?= esc(config('Silsilah')->jenisKegiatan[$k['jenis']] ?? $k['jenis']) ?></td>
                        <td class="small"><?= esc($k['lokasi']) ?></td>
                        <td><?= ['draft' => '<span class="badge text-bg-secondary">Draf</span>', 'terbit' => '<span class="badge text-bg-success">Terbit</span>', 'batal' => '<span class="badge text-bg-danger">Batal</span>'][$k['status']] ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/kegiatan/' . $k['id'] . '/ubah') ?>">Ubah</a>
                            <form method="post" action="<?= site_url('admin/kegiatan/' . $k['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus kegiatan ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= $pager->links('default', 'default_full') ?></div>
</div>
<?= $this->endSection() ?>
