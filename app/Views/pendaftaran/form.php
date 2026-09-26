<?php $cfg = config('Silsilah'); ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daftarkan Keluarga<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 1000px">
    <p class="eyebrow mb-1">Pendaftaran kepala keluarga</p>
    <h1 class="h2 mb-2">Daftarkan keluarga Anda sekali, untuk semua keturunan.</h1>
    <p class="text-teks-2 mb-4" style="max-width: 46rem">Isi silsilah Anda, lalu istri dan anak-anak. Anak yang kelak mendaftar cukup menekan <b>"Ini saya"</b>, jadi silsilah satu keluarga selalu sama.</p>

    <div class="row g-3 mb-4">
        <?php foreach ([
            ['1', 'Isi silsilah & keluarga', 'Pilih leluhur terdekat yang tercatat, lengkapi generasi antara, lalu istri dan anak.'],
            ['2', 'Validasi keluarga', 'Ayah/ompung atau anak/pahompu yang sudah menjadi member memastikan data benar.'],
            ['3', 'Pengesahan punguan', 'Penatua punguan (mis. Medan) mengesahkan, lalu Anda menjadi member.'],
        ] as [$no, $judul, $isi]) : ?>
            <div class="col-md-4"><div class="card card-body h-100 d-flex flex-row gap-3"><span class="linimasa"><span class="nomor"><?= $no ?></span></span><div><div class="fw-bold"><?= $judul ?></div><div class="small text-teks-2"><?= $isi ?></div></div></div></div>
        <?php endforeach ?>
    </div>

    <?php if ($ditolak) : ?>
        <div class="alert alert-danger"><b>Pendaftaran sebelumnya belum disahkan.</b> <?= esc($ditolak['catatan_verifikator'] ?? '') ?> Silakan periksa kembali dan ajukan ulang.</div>
    <?php endif ?>

    <div class="card card-body mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div>
                <div class="fw-bold"><i class="bi bi-search text-utama"></i> Nama Anda sudah tercatat?</div>
                <div class="small text-teks-2">Mungkin orang tua Anda sudah mendaftarkan Anda. Cari nama Anda dan tekan "Ini saya".</div>
            </div>
            <div class="position-relative flex-grow-1" style="max-width: 420px">
                <input type="hidden" id="klaim_id">
                <input type="search" class="form-control pilih-orang" data-target="klaim_id" autocomplete="off" placeholder="Ketik nama Anda…" aria-label="Cari nama Anda">
                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:20"></div>
            </div>
            <a class="btn btn-outline-secondary disabled" id="tombolKlaim" href="#" aria-disabled="true"><i class="bi bi-person-check"></i> Ini saya</a>
        </div>
    </div>

    <form method="post" action="<?= site_url('pendaftaran') ?>" class="card card-body p-4 langkah" id="formDaftar"
          data-batas="<?= $batas ?>" data-maks="<?= $maks ?>" data-validator-api="<?= site_url('pendaftaran/validator') ?>" data-klaim="<?= site_url('pendaftaran/klaim') ?>">
        <?= csrf_field() ?>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Punguan</h2>
            <p class="small text-teks-2">Punguan tempat Anda aktif. Penatua punguan ini yang akan mengesahkan pendaftaran Anda.</p>
            <select class="form-select" name="punguan_id" id="punguan_id" style="max-width: 420px" required>
                <?php foreach ($punguan as $p) : ?>
                    <option value="<?= $p['id'] ?>" <?= (string) old('punguan_id') === (string) $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Leluhur terdekat yang sudah tercatat</h2>
            <p class="small text-teks-2">Biasanya ayah atau ompung Anda. Sundut 1–<?= $batas ?> (Silsilah Pokok) sudah diisi dan disahkan Ketua Adat, jadi pilih mulai <b>Sundut <?= $batas ?></b>. Bila ibu Anda boru marga ini, pilih ibu Anda.</p>
            <div class="position-relative" style="max-width: 560px">
                <input type="hidden" name="leluhur_id" id="leluhur_id" value="<?= esc(old('leluhur_id')) ?>">
                <input type="search" class="form-control pilih-orang" data-target="leluhur_id" data-garis="utama,boru" autocomplete="off" placeholder="Cari nama ayah atau ompung Anda…" aria-label="Leluhur terdekat" required>
                <div class="list-group position-absolute w-100 shadow-sm hasil-pilih" style="z-index:20"></div>
            </div>
            <div id="infoLeluhur" class="small mt-2"></div>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Generasi yang belum tercatat</h2>
            <p class="small text-teks-2">Isi berurutan dari anak leluhur di atas sampai <b>ayah Anda</b>. Kosongkan bila yang dipilih adalah ayah/ibu Anda sendiri.</p>
            <div id="antara"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="tambahAntara"><i class="bi bi-plus-lg"></i> Tambah generasi</button>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Data kepala keluarga</h2>
            <div class="alert alert-light border small py-2" id="infoSundut">Pilih leluhur terlebih dahulu untuk melihat sundut Anda.</div>
            <div class="row g-3" id="wilayah" data-api="<?= site_url('api/wilayah') ?>"
                 data-provinsi="<?= esc(old('provinsi_kode'), 'attr') ?>" data-kabupaten="<?= esc(old('kabupaten_kode'), 'attr') ?>"
                 data-kecamatan="<?= esc(old('kecamatan_kode'), 'attr') ?>" data-desa="<?= esc(old('desa_kode'), 'attr') ?>">
                <div class="col-md-8"><label class="form-label" for="nama_lengkap">Nama lengkap <span class="text-danger">*</span></label><input class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= esc(old('nama_lengkap')) ?>" required maxlength="150" placeholder="Sesuai KTP, dengan marga"></div>
                <div class="col-md-4"><label class="form-label" for="jenis_kelamin">Jenis kelamin <span class="text-danger">*</span></label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required><option value="">– Pilih –</option><?php foreach ($cfg->jenisKelamin as $k => $v) : ?><option value="<?= $k ?>" <?= old('jenis_kelamin', 'L') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select></div>
                <div class="col-md-4"><label class="form-label" for="tempat_lahir">Tempat lahir</label><input class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= esc(old('tempat_lahir')) ?>" maxlength="100"></div>
                <div class="col-md-4"><label class="form-label" for="tanggal_lahir">Tanggal lahir</label><input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= esc(old('tanggal_lahir')) ?>"></div>
                <div class="col-md-4"><label class="form-label" for="no_hp">No. HP / WhatsApp</label><input class="form-control" id="no_hp" name="no_hp" value="<?= esc(old('no_hp')) ?>" inputmode="tel" maxlength="20" placeholder="08…"></div>
                <div class="col-md-6"><label class="form-label" for="nama_ibu">Nama ibu</label><input class="form-control" id="nama_ibu" name="nama_ibu" value="<?= esc(old('nama_ibu')) ?>" maxlength="150" placeholder="mis. Rosmawati br. Simanjuntak"></div>
                <div class="col-md-6"><label class="form-label" for="alamat_jalan">Alamat</label><input class="form-control" id="alamat_jalan" name="alamat_jalan" value="<?= esc(old('alamat_jalan')) ?>" maxlength="255"></div>
                <div class="col-md-6"><label class="form-label" for="provinsi_kode">Provinsi domisili <span class="text-danger">*</span></label><select class="form-select" id="provinsi_kode" name="provinsi_kode"></select></div>
                <div class="col-md-6"><label class="form-label" for="kabupaten_kode">Kabupaten/kota <span class="text-danger">*</span></label><select class="form-select" id="kabupaten_kode" name="kabupaten_kode" disabled></select></div>
                <div class="col-md-6"><label class="form-label" for="kecamatan_kode">Kecamatan</label><select class="form-select" id="kecamatan_kode" name="kecamatan_kode" disabled></select></div>
                <div class="col-md-6"><label class="form-label" for="desa_kode">Desa/kelurahan</label><select class="form-select" id="desa_kode" name="desa_kode" disabled></select></div>
            </div>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Istri dan anak-anak</h2>
            <p class="small text-teks-2">Anak yang sudah menikah tetap dicatat di sini; nanti ia cukup menekan "Ini saya" untuk memakai akunnya sendiri.</p>
            <div class="row g-3 mb-3">
                <div class="col-md-5"><label class="form-label" for="istri_nama">Nama istri/suami</label><input class="form-control" id="istri_nama" name="istri_nama" value="<?= esc(old('istri_nama')) ?>" maxlength="150" placeholder="mis. Tiurma br. Sitompul"></div>
                <div class="col-md-4"><label class="form-label" for="istri_marga">Marga</label><input class="form-control" id="istri_marga" name="istri_marga" value="<?= esc(old('istri_marga')) ?>" maxlength="100" placeholder="mis. Sitompul"></div>
                <div class="col-md-3"><label class="form-label" for="istri_tahun">Tahun lahir</label><input class="form-control" id="istri_tahun" name="istri_tahun" type="number" min="1900" max="<?= date('Y') ?>" value="<?= esc(old('istri_tahun')) ?>"></div>
            </div>
            <div class="label-baris">Anak</div>
            <div id="daftarAnak"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="tambahAnak"><i class="bi bi-plus-lg"></i> Tambah anak</button>
        </div>

        <div class="langkah-item mb-4">
            <h2 class="h5 mb-1">Validator keluarga</h2>
            <p class="small text-teks-2">Anggota sah dalam garis langsung Anda (orang tua atau ompung) yang akan memastikan data ini benar.</p>
            <div id="kandidatValidator" class="small text-teks-2">Pilih leluhur terlebih dahulu.</div>
        </div>

        <div class="langkah-item">
            <h2 class="h5 mb-1">Catatan untuk penatua</h2>
            <textarea class="form-control" id="catatan" name="catatan" rows="2" maxlength="1000" placeholder="Sumber: buku tarombo keluarga, kerabat yang mengenal Anda, dll."><?= esc(old('catatan')) ?></textarea>
        </div>

        <div class="d-flex justify-content-end mt-4"><button class="btn btn-utama btn-lg px-4"><i class="bi bi-send"></i> Kirim pendaftaran keluarga</button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>window.SILSILAH_URL = <?= json_encode(rtrim(site_url('/'), '/') . '/') ?>;</script>
<script src="<?= base_url('assets/js/pilih-orang.js') ?>"></script>
<script src="<?= base_url('assets/js/form-anggota.js') ?>"></script>
<script src="<?= base_url('assets/js/pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
