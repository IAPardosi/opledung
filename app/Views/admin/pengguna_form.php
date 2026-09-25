<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Atur Pengguna<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 640px">
    <a href="<?= site_url('admin/pengguna') ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar pengguna</a>
    <h1 class="h3 mt-2 mb-3">Atur Akun <?= esc($akun->username) ?></h1>
    <form method="post" class="card card-body">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="grup">Role</label>
            <select class="form-select" id="grup" name="grup">
                <?php foreach ($label as $k => $g) : ?>
                    <option value="<?= esc($k, 'attr') ?>" <?= $grup === $k ? 'selected' : '' ?>><?= esc($g['title']) ?>: <?= esc($g['description']) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="marga_id">Marga</label>
            <select class="form-select" id="marga_id" name="marga_id">
                <option value="">– Tidak ada –</option>
                <?php foreach ($margas as $m) : ?>
                    <option value="<?= $m['id'] ?>" <?= (int) $akun->marga_id === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['nama']) ?></option>
                <?php endforeach ?>
            </select>
            <div class="form-text">Ketua Adat, Verifikator, dan Member hanya berwenang di marga ini.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="kode_anggota">Tautkan ke data silsilah (kode anggota)</label>
            <input class="form-control font-monospace" id="kode_anggota" name="kode_anggota" value="<?= esc(old('kode_anggota', $person?->kode_anggota ?? '')) ?>" placeholder="mis. PDS-G12-000345">
            <?php if ($person) : ?><div class="form-text">Saat ini: <a href="<?= site_url('anggota/' . $person->id) ?>"><?= esc($person->nama_lengkap) ?></a>. Kosongkan untuk melepas tautan.</div><?php endif ?>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $akun->active ? 'checked' : '' ?>>
            <label class="form-check-label" for="active">Akun aktif</label>
        </div>
        <div class="text-end"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
