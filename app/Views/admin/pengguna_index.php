<?php $label = config('AuthGroups')->groups; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Pengguna<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Pengguna</h1>
        <form class="d-flex gap-2" method="get">
            <input type="search" class="form-control" name="q" value="<?= esc($cari) ?>" placeholder="Cari username atau nama…">
            <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Username</th><th>Email</th><th>Role</th><th>Marga</th><th>Data silsilah</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $u) : ?>
                    <tr>
                        <td class="fw-medium"><?= esc($u['username']) ?></td>
                        <td class="small"><?= esc($u['email'] ?? '') ?></td>
                        <td><?php foreach (array_filter(explode(',', (string) $u['grup'])) as $g) : ?><span class="badge text-bg-light border"><?= esc($label[$g]['title'] ?? $g) ?></span> <?php endforeach ?></td>
                        <td><?= esc($u['nama_marga'] ?? '–') ?></td>
                        <td class="small"><?= $u['person_id'] ? '<a href="' . site_url('anggota/' . $u['person_id']) . '">' . esc($u['nama_person']) . '</a><div class="font-monospace text-body-secondary">' . esc($u['kode_anggota']) . '</div>' : '<span class="text-body-secondary">Belum tertaut</span>' ?></td>
                        <td><?= $u['active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/pengguna/' . $u['id'] . '/ubah') ?>">Atur</a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= $pager->links('default', 'default_full') ?></div>
</div>
<?= $this->endSection() ?>
