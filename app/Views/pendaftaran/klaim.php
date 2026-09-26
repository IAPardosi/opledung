<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ini Saya<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 760px">
    <p class="eyebrow mb-1">Klaim data silsilah</p>
    <h1 class="h2 mb-3">Apakah ini Anda?</h1>
    <div class="card card-body mb-4 d-flex flex-row gap-3 align-items-center">
        <span class="avatar avatar-sm garis-<?= esc($person->garis, 'attr') ?>"><?= esc(inisial($person->nama_lengkap)) ?></span>
        <div>
            <div class="fw-bold fs-5"><?= esc($person->nama_lengkap) ?></div>
            <div class="small text-teks-2"><?= esc($person->kode_anggota) ?> · Sundut <?= $person->generasi_ke ?><?= $person->tahun_lahir ? ' · l. ' . $person->tahun_lahir : '' ?></div>
        </div>
    </div>
    <form method="post" class="card card-body p-4">
        <?= csrf_field() ?>
        <?php if ($punguan !== []) : ?>
            <label class="form-label" for="punguan_id">Punguan Anda</label>
            <select class="form-select mb-2" id="punguan_id" name="punguan_id" style="max-width: 360px">
                <?php foreach ($punguan as $p) : ?><option value="<?= $p['id'] ?>" <?= (int) (auth()->user()->punguan_id ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?></option><?php endforeach ?>
            </select>
        <?php endif ?>
        <?= view('partials/pilih_validator', ['kandidat' => $kandidat]) ?>
        <div class="form-section">Catatan</div>
        <textarea class="form-control" name="catatan" rows="2" maxlength="1000" placeholder="mis. Saya anak kedua dari ayah yang tercatat."><?= esc(old('catatan')) ?></textarea>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= site_url('pendaftaran') ?>" class="btn btn-light">Batal</a>
            <button class="btn btn-utama px-4"><i class="bi bi-person-check"></i> Ya, ini saya</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
