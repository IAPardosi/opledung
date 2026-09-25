<?php $cfg = config('Silsilah'); ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Pendaftaran Silsilah<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 980px">
    <p class="eyebrow mb-1">Pendaftaran member</p>
    <div class="judul-bagian"><h1 class="h2">Lengkapi Silsilah Anda</h1></div>

    <div class="row g-3 mb-4">
        <?php foreach ([
            ['bi-diagram-2', 'Isi silsilah', 'Pilih leluhur terdekat yang sudah tercatat, lalu isi generasi di antaranya sampai Anda.'],
            ['bi-people', 'Kesaksian keluarga', 'Kerabat dekat yang sudah terverifikasi menyatakan data Anda benar atau salah.'],
            ['bi-patch-check', 'Validasi admin', 'Admin Wilayah (sesuai domisili/cabang Anda) memeriksa dan menyetujui.'],
        ] as [$ikon, $judul, $isi]) : ?>
            <div class="col-md-4"><div class="card card-body h-100"><i class="bi <?= $ikon ?> fs-3 text-utama"></i><div class="fw-semibold mt-1"><?= $judul ?></div><div class="small text-teks-2"><?= $isi ?></div></div></div>
        <?php endforeach ?>
    </div>

    <?php if ($ditolak) : ?>
        <div class="alert alert-danger"><b>Pendaftaran sebelumnya tidak disetujui.</b> <?= esc($ditolak['catatan_verifikator'] ?? '') ?> Silakan periksa kembali dan ajukan ulang.</div>
    <?php endif ?>

    <div class="card card-body mb-4">
        <div class="fw-semibold"><i class="bi bi-search text-utama"></i> Nama Anda sudah tercatat di silsilah?</div>
        <p class="small text-teks-2 mb-2">Cari nama Anda. Bila ada, ajukan <b>"Ini saya"</b> tanpa perlu mengisi silsilah dari awal.</p>
        <form method="post" id="formKlaim" class="row g-2 align-items-start">
            <?= csrf_field() ?>
            <div class="col-md-9 position-relative">
                <input type="hidden" id="klaim_id">
                <input type="search" class="form-control pilih-orang" data-target="klaim_id" autocomplete="off" placeholder="Ketik nama Anda…">
                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:20"></div>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-secondary" id="tombolKlaim" disabled><i class="bi bi-person-check"></i> Ini saya</button></div>
        </form>
    </div>

    <form method="post" action="<?= site_url('pendaftaran') ?>" class="card card-body langkah" id="formDaftar" data-batas="<?= $batas ?>" data-maks="<?= $maks ?>">
        <?= csrf_field() ?>
        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Leluhur terdekat yang sudah tercatat</h2>
            <p class="small text-teks-2">Biasanya ayah atau ompung Anda. Generasi 1–<?= $batas ?> (Silsilah Pokok) sudah diisi dan divalidasi Ketua Adat, jadi pilih leluhur mulai <b>Sundut <?= $batas ?></b>.
                Bila ibu Anda boru marga ini, pilih ibu Anda.</p>
            <div class="position-relative" style="max-width: 560px">
                <input type="hidden" name="leluhur_id" id="leluhur_id" value="<?= esc(old('leluhur_id')) ?>">
                <input type="search" class="form-control pilih-orang" data-target="leluhur_id" data-garis="utama,boru" autocomplete="off" placeholder="Cari nama ayah atau ompung Anda…" required>
                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:20"></div>
            </div>
            <div id="infoLeluhur" class="small mt-2"></div>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Generasi yang belum tercatat</h2>
            <p class="small text-teks-2">Isi berurutan dari anak leluhur di atas sampai <b>ayah Anda</b>. Kosongkan bila leluhur yang dipilih adalah ayah/ibu Anda sendiri.</p>
            <div id="antara"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="tambahAntara"><i class="bi bi-plus-lg"></i> Tambah generasi</button>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Data diri</h2>
            <div class="alert alert-light border small py-2" id="infoSundut">Pilih leluhur terlebih dahulu untuk melihat sundut Anda.</div>
            <div class="row g-3" id="wilayah" data-api="<?= site_url('api/wilayah') ?>"
                 data-provinsi="<?= esc(old('provinsi_kode'), 'attr') ?>" data-kabupaten="<?= esc(old('kabupaten_kode'), 'attr') ?>"
                 data-kecamatan="<?= esc(old('kecamatan_kode'), 'attr') ?>" data-desa="<?= esc(old('desa_kode'), 'attr') ?>">
                <div class="col-md-8"><label class="form-label" for="nama_lengkap">Nama lengkap <span class="text-danger">*</span></label><input class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= esc(old('nama_lengkap')) ?>" required maxlength="150" placeholder="Sesuai KTP, dengan marga"></div>
                <div class="col-md-4"><label class="form-label" for="jenis_kelamin">Jenis kelamin <span class="text-danger">*</span></label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required><option value="">– Pilih –</option><?php foreach ($cfg->jenisKelamin as $k => $v) : ?><option value="<?= $k ?>" <?= old('jenis_kelamin') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select></div>
                <div class="col-md-4"><label class="form-label" for="nama_panggilan">Nama panggilan</label><input class="form-control" id="nama_panggilan" name="nama_panggilan" value="<?= esc(old('nama_panggilan')) ?>" maxlength="100"></div>
                <div class="col-md-4"><label class="form-label" for="tempat_lahir">Tempat lahir</label><input class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= esc(old('tempat_lahir')) ?>" maxlength="100"></div>
                <div class="col-md-4"><label class="form-label" for="tanggal_lahir">Tanggal lahir</label><input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= esc(old('tanggal_lahir')) ?>"></div>
                <div class="col-md-6"><label class="form-label" for="nama_ibu">Nama ibu</label><input class="form-control" id="nama_ibu" name="nama_ibu" value="<?= esc(old('nama_ibu')) ?>" maxlength="150" placeholder="mis. Rosmawati br. Simanjuntak"></div>
                <div class="col-md-6"><label class="form-label" for="no_hp">No. HP / WhatsApp</label><input class="form-control" id="no_hp" name="no_hp" value="<?= esc(old('no_hp')) ?>" inputmode="tel" maxlength="20" placeholder="08…"></div>
                <div class="col-12"><label class="form-label" for="alamat_jalan">Alamat</label><input class="form-control" id="alamat_jalan" name="alamat_jalan" value="<?= esc(old('alamat_jalan')) ?>" maxlength="255"></div>
                <div class="col-md-6"><label class="form-label" for="provinsi_kode">Provinsi domisili <span class="text-danger">*</span></label><select class="form-select" id="provinsi_kode" name="provinsi_kode"></select></div>
                <div class="col-md-6"><label class="form-label" for="kabupaten_kode">Kabupaten/Kota <span class="text-danger">*</span></label><select class="form-select" id="kabupaten_kode" name="kabupaten_kode" disabled></select></div>
                <div class="col-md-6"><label class="form-label" for="kecamatan_kode">Kecamatan</label><select class="form-select" id="kecamatan_kode" name="kecamatan_kode" disabled></select></div>
                <div class="col-md-6"><label class="form-label" for="desa_kode">Desa/Kelurahan</label><select class="form-select" id="desa_kode" name="desa_kode" disabled></select></div>
            </div>
        </div>

        <div class="langkah-item">
            <h2 class="h5 mb-1">Untuk memudahkan validasi</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="kerabat">Kerabat yang mengenal Anda</label><input class="form-control" id="kerabat" name="kerabat" value="<?= esc(old('kerabat')) ?>" maxlength="300" placeholder="mis. Tulang saya: Sahat Pardosi (Medan)"></div>
                <div class="col-md-6"><label class="form-label" for="catatan">Catatan</label><input class="form-control" id="catatan" name="catatan" value="<?= esc(old('catatan')) ?>" maxlength="500" placeholder="Sumber: buku tarombo keluarga, dll."></div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4"><button class="btn btn-utama btn-lg px-4"><i class="bi bi-send"></i> Kirim pendaftaran</button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>window.SILSILAH_URL = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;</script>
<script src="<?= base_url('assets/js/pilih-orang.js') ?>"></script>
<script src="<?= base_url('assets/js/form-anggota.js') ?>"></script>
<script src="<?= base_url('assets/js/pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
