<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daftar Generasi<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Daftar Generasi</h1>
            <p class="text-body-secondary mb-0">Marga <?= esc($marga['nama']) ?><?= $generasi ? ' · Generasi ' . $generasi : '' ?></p>
        </div>
    </div>

    <form class="card card-body mb-3" method="get" action="<?= site_url('generasi') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small" for="q">Nama atau kode anggota</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= esc($cari) ?>" placeholder="mis. Hotman atau PDS-G12-000123">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="g">Generasi</label>
                <select class="form-select" id="g" name="g">
                    <option value="">Semua generasi</option>
                    <?php foreach ($rekap as $r) : ?>
                        <option value="<?= $r['generasi_ke'] ?>" <?= $generasi === $r['generasi_ke'] ? 'selected' : '' ?>>Generasi <?= $r['generasi_ke'] ?> (<?= $r['utama'] + $r['boru'] ?>)</option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="garis">Garis</label>
                <select class="form-select" id="garis" name="garis">
                    <option value="">Utama & boru</option>
                    <?php foreach (['utama' => 'Garis utama', 'boru' => 'Boru', 'anak_boru' => 'Anak boru'] as $k => $v) : ?>
                        <option value="<?= $k ?>" <?= $garis === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-utama" type="submit"><i class="bi bi-search"></i> Tampilkan</button></div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Nama</th><th>Kode</th><th class="text-center">Gen.</th><th>Garis</th><th>Anak dari</th><th class="text-end">Silsilah</th></tr>
                </thead>
                <tbody>
                <?php if ($rows === []) : ?>
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Tidak ada anggota yang cocok.</td></tr>
                <?php endif ?>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('anggota/' . $r['id']) ?>" class="fw-medium text-decoration-none"><?= esc($r['nama_lengkap']) ?></a>
                            <?php if ($r['gelar_adat']) : ?><div class="small text-body-secondary"><?= esc($r['gelar_adat']) ?></div><?php endif ?>
                        </td>
                        <td class="small font-monospace"><?= esc($r['kode_anggota']) ?></td>
                        <td class="text-center"><?= (int) $r['generasi_ke'] ?></td>
                        <td><?= badge_garis($r['garis']) ?></td>
                        <td class="small"><?= $r['nama_induk'] ? '<a class="text-decoration-none" href="' . site_url('anggota/' . $r['induk_id']) . '">' . esc($r['nama_induk']) . '</a>' : '–' ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('silsilah/' . $r['id']) ?>" title="Lihat di pohon"><i class="bi bi-diagram-3"></i></a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= $pager->links('default', 'default_full') ?></div>
    <?php if (! auth()->loggedIn()) : ?>
        <p class="small text-body-secondary mt-2"><i class="bi bi-lock"></i> Profil lengkap setiap anggota dapat dilihat setelah <a href="<?= site_url('login') ?>">masuk</a>.</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
