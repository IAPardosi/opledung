<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kelola Berita<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Kelola Berita</h1>
        <a href="<?= site_url('admin/berita/tambah') ?>" class="btn btn-utama"><i class="bi bi-plus-lg"></i> Tulis berita</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Terbit</th><th class="text-end">Dibaca</th><th></th></tr></thead>
                <tbody>
                <?php if ($rows === []) : ?><tr><td colspan="6" class="text-center text-teks-2 py-4">Belum ada berita.</td></tr><?php endif ?>
                <?php foreach ($rows as $b) : ?>
                    <tr>
                        <td class="fw-medium"><?= esc($b['judul']) ?></td>
                        <td><span class="kategori kategori-<?= esc($b['kategori'], 'attr') ?>"><?= esc(config('Silsilah')->kategoriBerita[$b['kategori']]['label'] ?? $b['kategori']) ?></span></td>
                        <td><?= $b['status'] === 'terbit' ? '<span class="badge text-bg-success">Terbit</span>' : '<span class="badge text-bg-secondary">Draf</span>' ?></td>
                        <td class="small"><?= esc(tanggal_indo($b['terbit_at'])) ?></td>
                        <td class="text-end small"><?= (int) $b['dilihat'] ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($b['status'] === 'terbit') : ?><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('berita/' . $b['slug']) ?>" target="_blank"><i class="bi bi-eye"></i></a><?php endif ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/berita/' . $b['id'] . '/ubah') ?>">Ubah</a>
                            <form method="post" action="<?= site_url('admin/berita/' . $b['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus berita ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button></form>
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
