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
        <div class="mb-3">
            <label class="form-label" for="punguan_id">Punguan (keanggotaan)</label>
            <select class="form-select" id="punguan_id" name="punguan_id">
                <option value="">– Tidak ada –</option>
                <?php foreach ($punguan as $pg) : ?><option value="<?= $pg['id'] ?>" <?= (int) ($akun->punguan_id ?? 0) === (int) $pg['id'] ? 'selected' : '' ?>><?= esc($pg['nama']) ?></option><?php endforeach ?>
            </select>
        </div>
        <fieldset class="border rounded-4 p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-0">Penatua punguan</legend>
            <p class="small text-body-secondary">Untuk role Penatua Punguan: punguan yang pendaftarannya ia sahkan.</p>
            <?php foreach ($punguan as $pg) : ?>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="lingkup_punguan[]" value="<?= $pg['id'] ?>" id="lp<?= $pg['id'] ?>" <?= in_array((string) $pg['id'], $punguanLingkup, true) ? 'checked' : '' ?>><label class="form-check-label" for="lp<?= $pg['id'] ?>"><?= esc($pg['nama']) ?></label></div>
            <?php endforeach ?>
        </fieldset>
        <fieldset class="border rounded-3 p-3 mb-3" id="lingkupAdmin" data-api="<?= site_url('api/wilayah') ?>">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-0">Lingkup tambahan (wilayah / cabang)</legend>
            <p class="small text-body-secondary">Hanya untuk role Admin Wilayah. Admin memverifikasi pendaftaran dan usulan dari anggota yang tinggal di wilayahnya
                <b>atau</b> yang termasuk pomparan (keturunan) leluhur cabang yang ia kenal.</p>
            <label class="form-label small" for="lingkup_wilayah">Kabupaten/kota</label>
            <select class="form-select mb-3" id="lingkup_wilayah" name="lingkup_wilayah[]" multiple size="8" data-kabupaten data-pilih-banyak="<?= esc(implode(',', $wilayahTerpilih), 'attr') ?>"></select>
            <label class="form-label small" for="lingkup_cabang">Cabang pomparan (kode anggota leluhur, pisahkan dengan koma)</label>
            <input class="form-control font-monospace" id="lingkup_cabang" name="lingkup_cabang" value="<?= esc(old('lingkup_cabang', implode(', ', array_map(static fn ($c) => $c->kode_anggota, $cabang)))) ?>" placeholder="mis. PDS-G09-000210">
            <?php foreach ($cabang as $c) : ?><div class="small text-body-secondary mt-1"><i class="bi bi-diagram-2"></i> Pomparan <?= esc($c->nama_lengkap) ?> (Sundut <?= $c->generasi_ke ?>)</div><?php endforeach ?>
        </fieldset>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $akun->active ? 'checked' : '' ?>>
            <label class="form-check-label" for="active">Akun aktif</label>
        </div>
        <div class="text-end"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/pilih-kabupaten.js') ?>"></script>
<?= $this->endSection() ?>
