<?php
/**
 * Form data orang: tambah anak, tambah pasangan, atau ubah data.
 *
 * @var string                     $mode     anak|pasangan|ubah|leluhur
 * @var \App\Entities\Person|null  $person   data yang diubah (mode ubah)
 * @var \App\Entities\Person|null  $induk    orang tua / pasangan (mode anak/pasangan)
 */
$cfg   = config('Silsilah');
$raw   = $person?->toRawArray() ?? [];
$nilai = static fn (string $k, $bawaan = '') => old($k, $raw[$k] ?? $bawaan);
$pilih = static function (string $nama, array $opsi, $terpilih, string $kosong = '– Pilih –'): string {
    $html = '<select class="form-select" id="' . $nama . '" name="' . $nama . '"><option value="">' . esc($kosong) . '</option>';
    foreach ($opsi as $k => $v) {
        $k    = is_int($k) ? $v : $k;
        $html .= '<option value="' . esc($k, 'attr') . '"' . ((string) $terpilih === (string) $k ? ' selected' : '') . '>' . esc($v) . '</option>';
    }

    return $html . '</select>';
};
$garisOrang   = $person?->garis;
$tampilMarga  = $mode === 'pasangan' || in_array($garisOrang, ['pasangan', 'anak_boru'], true);
$ringkas      = $mode === 'anak' && $induk?->garis === 'boru';
$kembali      = ($person?->id ?? $induk?->id) ? site_url('anggota/' . ($person?->id ?? $induk?->id)) : site_url('silsilah');
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 920px">
    <a href="<?= $kembali ?>" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>
    <h1 class="h3 mt-2 mb-1"><?= esc($judul) ?></h1>
    <?php if (! $langsung) : ?>
        <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Isian Anda akan dikirim sebagai <b>usulan</b> dan baru masuk ke silsilah setelah disetujui Verifikator<?= $mode === 'ubah' && $person?->generasi_ke <= 10 ? ' atau Ketua Adat' : '' ?>.</div>
    <?php endif ?>
    <?php if ($mode === 'anak') : ?>
        <p class="text-body-secondary">Anak akan tercatat di <b>Generasi <?= $induk->generasi_ke + 1 ?></b>.
            <?= $induk->garis === 'boru'
                ? 'Karena induknya boru, anak ini dicatat sebagai <b>anak boru</b> (ujung cabang, tidak diteruskan).'
                : 'Anak laki-laki meneruskan garis utama; anak perempuan dicatat sebagai boru.' ?></p>
    <?php endif ?>

    <form method="post" action="<?= esc($aksi, 'attr') ?>" enctype="multipart/form-data" class="card card-body mt-3">
        <?= csrf_field() ?>

        <div class="form-section">Identitas</div>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="nama_lengkap">Nama lengkap <span class="text-danger">*</span></label>
                <input class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= esc($nilai('nama_lengkap')) ?>" required maxlength="150" placeholder="Sesuai KTP">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="nama_panggilan">Nama panggilan</label>
                <input class="form-control" id="nama_panggilan" name="nama_panggilan" value="<?= esc($nilai('nama_panggilan')) ?>" maxlength="100">
            </div>
            <?php if (in_array($mode, ['anak', 'leluhur'], true)) : ?>
            <div class="col-md-4">
                <label class="form-label" for="jenis_kelamin">Jenis kelamin <span class="text-danger">*</span></label>
                <?php if ($mode === 'leluhur') : ?>
                    <input type="hidden" name="jenis_kelamin" value="L"><input class="form-control" value="Laki-laki" disabled>
                <?php else : ?>
                    <?= $pilih('jenis_kelamin', $cfg->jenisKelamin, $nilai('jenis_kelamin')) ?>
                <?php endif ?>
            </div>
            <?php endif ?>
            <?php if ($mode === 'anak') : ?>
            <div class="col-md-2">
                <label class="form-label" for="urutan_anak">Anak ke-</label>
                <input type="number" min="1" max="99" class="form-control" id="urutan_anak" name="urutan_anak" value="<?= esc($nilai('urutan_anak')) ?>" placeholder="otomatis">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="pasangan_id"><?= $induk->garis === 'boru' ? 'Ayah (suami boru)' : 'Ibu' ?></label>
                <?php if ($pasangan !== []) : ?>
                    <?= $pilih('pasangan_id', array_column(array_map(static fn ($ps) => ['id' => $ps['id'], 'n' => $ps['nama_lengkap']], $pasangan), 'n', 'id'), old('pasangan_id', count($pasangan) === 1 ? $pasangan[0]['id'] : ''), '– Tidak diketahui –') ?>
                <?php else : ?>
                    <input class="form-control" value="Belum ada pasangan tercatat" disabled>
                    <div class="form-text"><a href="<?= site_url('anggota/' . $induk->id . '/tambah-pasangan') ?>">Tambahkan pasangan</a> terlebih dahulu, atau isi nama ibu di bawah.</div>
                <?php endif ?>
            </div>
            <?php endif ?>
            <?php if ($mode !== 'leluhur' && $mode !== 'pasangan' && $garisOrang !== 'pasangan') : ?>
            <div class="col-md-6">
                <label class="form-label" for="nama_ibu">Nama ibu <span class="small text-body-secondary">(bila ibu belum tercatat)</span></label>
                <input class="form-control" id="nama_ibu" name="nama_ibu" value="<?= esc($nilai('nama_ibu')) ?>" maxlength="150">
            </div>
            <?php endif ?>
            <div class="col-md-6">
                <label class="form-label" for="gelar_adat">Gelar adat / nama sapaan</label>
                <input class="form-control" id="gelar_adat" name="gelar_adat" value="<?= esc($nilai('gelar_adat')) ?>" maxlength="150" placeholder="mis. Op. …, Ama ni …">
            </div>
            <?php if ($tampilMarga) : ?>
            <div class="col-md-6">
                <label class="form-label" for="marga_nama">Marga <?= $mode === 'pasangan' ? '<span class="text-danger">*</span>' : '' ?></label>
                <input class="form-control" id="marga_nama" name="marga_nama" value="<?= esc($nilai('marga_nama')) ?>" maxlength="100" <?= $mode === 'pasangan' ? 'required' : '' ?>>
            </div>
            <?php endif ?>
        </div>

        <?php if ($mode === 'pasangan') : ?>
        <div class="form-section">Pernikahan</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="tanggal_nikah">Tanggal menikah</label>
                <input type="date" class="form-control" id="tanggal_nikah" name="tanggal_nikah" value="<?= esc(old('tanggal_nikah')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tempat_nikah">Tempat menikah</label>
                <input class="form-control" id="tempat_nikah" name="tempat_nikah" value="<?= esc(old('tempat_nikah')) ?>" maxlength="150">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status_nikah">Status pernikahan</label>
                <?= $pilih('status_nikah', ['menikah' => 'Menikah', 'cerai_hidup' => 'Cerai hidup', 'cerai_mati' => 'Cerai mati'], old('status_nikah', 'menikah'), '– Pilih –') ?>
            </div>
        </div>
        <?php endif ?>

        <div class="form-section">Kelahiran & Status</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="tempat_lahir">Tempat lahir</label>
                <input class="form-control" id="tempat_lahir" name="tempat_lahir" value="<?= esc($nilai('tempat_lahir')) ?>" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tanggal_lahir">Tanggal lahir</label>
                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?= esc($nilai('tanggal_lahir')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tahun_lahir">atau tahun lahir saja</label>
                <input type="number" min="1000" max="<?= date('Y') ?>" class="form-control" id="tahun_lahir" name="tahun_lahir" value="<?= esc($nilai('tahun_lahir')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status_hidup">Status</label>
                <?= $pilih('status_hidup', $cfg->statusHidup, $nilai('status_hidup', 'hidup'), '– Pilih –') ?>
            </div>
            <div class="col-md-4 wafat">
                <label class="form-label" for="tanggal_wafat">Tanggal wafat</label>
                <input type="date" class="form-control" id="tanggal_wafat" name="tanggal_wafat" value="<?= esc($nilai('tanggal_wafat')) ?>">
            </div>
            <div class="col-md-4 wafat">
                <label class="form-label" for="tahun_wafat">atau tahun wafat saja</label>
                <input type="number" min="1000" max="<?= date('Y') ?>" class="form-control" id="tahun_wafat" name="tahun_wafat" value="<?= esc($nilai('tahun_wafat')) ?>">
            </div>
            <div class="col-md-12 wafat">
                <label class="form-label" for="tempat_makam">Tempat makam / tugu</label>
                <input class="form-control" id="tempat_makam" name="tempat_makam" value="<?= esc($nilai('tempat_makam')) ?>" maxlength="255">
            </div>
        </div>

        <?php if (! $ringkas) : ?>
        <div class="form-section">Data Kependudukan</div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="agama">Agama</label><?= $pilih('agama', $cfg->agama, $nilai('agama')) ?></div>
            <div class="col-md-4"><label class="form-label" for="status_perkawinan">Status perkawinan</label><?= $pilih('status_perkawinan', $cfg->statusPerkawinan, $nilai('status_perkawinan')) ?></div>
            <div class="col-md-4"><label class="form-label" for="pendidikan_terakhir">Pendidikan terakhir</label><?= $pilih('pendidikan_terakhir', $cfg->pendidikan, $nilai('pendidikan_terakhir')) ?></div>
            <div class="col-md-4">
                <label class="form-label" for="pekerjaan">Pekerjaan</label>
                <input class="form-control" id="pekerjaan" name="pekerjaan" value="<?= esc($nilai('pekerjaan')) ?>" maxlength="100">
            </div>
            <div class="col-md-4"><label class="form-label" for="golongan_darah">Golongan darah</label><?= $pilih('golongan_darah', $cfg->golonganDarah, $nilai('golongan_darah')) ?></div>
            <div class="col-md-4"><label class="form-label" for="kewarganegaraan">Kewarganegaraan</label><?= $pilih('kewarganegaraan', $cfg->kewarganegaraan, $nilai('kewarganegaraan', 'WNI'), '– Pilih –') ?></div>
            <div class="col-md-6">
                <label class="form-label" for="nik">NIK</label>
                <input class="form-control font-monospace" id="nik" name="nik" inputmode="numeric" pattern="\d{16}" maxlength="16" autocomplete="off"
                       placeholder="<?= $person?->nik_enc ? 'Sudah tersimpan · isi hanya untuk mengganti' : '16 digit' ?>">
                <div class="form-text"><i class="bi bi-shield-lock"></i> Opsional. Disimpan terenkripsi, hanya terlihat oleh admin.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="no_kk">No. KK</label>
                <input class="form-control font-monospace" id="no_kk" name="no_kk" inputmode="numeric" pattern="\d{16}" maxlength="16" autocomplete="off"
                       placeholder="<?= $person?->no_kk_enc ? 'Sudah tersimpan · isi hanya untuk mengganti' : '16 digit' ?>">
            </div>
        </div>

        <div class="form-section">Alamat & Kontak</div>
        <div class="row g-3" id="wilayah"
             data-api="<?= site_url('api/wilayah') ?>"
             data-provinsi="<?= esc($nilai('provinsi_kode'), 'attr') ?>" data-kabupaten="<?= esc($nilai('kabupaten_kode'), 'attr') ?>"
             data-kecamatan="<?= esc($nilai('kecamatan_kode'), 'attr') ?>" data-desa="<?= esc($nilai('desa_kode'), 'attr') ?>">
            <div class="col-12">
                <label class="form-label" for="alamat_jalan">Alamat (jalan, nomor rumah)</label>
                <input class="form-control" id="alamat_jalan" name="alamat_jalan" value="<?= esc($nilai('alamat_jalan')) ?>" maxlength="255">
            </div>
            <div class="col-3 col-md-2"><label class="form-label" for="rt">RT</label><input class="form-control" id="rt" name="rt" value="<?= esc($nilai('rt')) ?>" inputmode="numeric" maxlength="3"></div>
            <div class="col-3 col-md-2"><label class="form-label" for="rw">RW</label><input class="form-control" id="rw" name="rw" value="<?= esc($nilai('rw')) ?>" inputmode="numeric" maxlength="3"></div>
            <div class="col-6 col-md-3"><label class="form-label" for="kode_pos">Kode pos</label><input class="form-control" id="kode_pos" name="kode_pos" value="<?= esc($nilai('kode_pos')) ?>" inputmode="numeric" maxlength="5"></div>
            <div class="col-md-6"><label class="form-label" for="provinsi_kode">Provinsi</label><select class="form-select" id="provinsi_kode" name="provinsi_kode"></select></div>
            <div class="col-md-6"><label class="form-label" for="kabupaten_kode">Kabupaten/Kota</label><select class="form-select" id="kabupaten_kode" name="kabupaten_kode" disabled></select></div>
            <div class="col-md-6"><label class="form-label" for="kecamatan_kode">Kecamatan</label><select class="form-select" id="kecamatan_kode" name="kecamatan_kode" disabled></select></div>
            <div class="col-md-6"><label class="form-label" for="desa_kode">Desa/Kelurahan</label><select class="form-select" id="desa_kode" name="desa_kode" disabled></select></div>
            <div class="col-md-6">
                <label class="form-label" for="no_hp">No. HP / WhatsApp</label>
                <input class="form-control" id="no_hp" name="no_hp" value="<?= esc($nilai('no_hp')) ?>" inputmode="tel" placeholder="08…" maxlength="20">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= esc($nilai('email')) ?>" maxlength="150">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sembunyikan_kontak" name="sembunyikan_kontak" value="1" <?= $nilai('sembunyikan_kontak') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sembunyikan_kontak">Sembunyikan alamat dan kontak dari member lain</label>
                </div>
            </div>
        </div>

        <div class="form-section">Profil</div>
        <div class="row g-3">
            <?php if ($mode === 'ubah' && $langsung) : ?>
            <div class="col-md-6">
                <label class="form-label" for="foto">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG/PNG/WebP, maksimal 2 MB.</div>
            </div>
            <?php endif ?>
            <div class="col-12">
                <label class="form-label" for="biografi">Riwayat singkat / biografi</label>
                <textarea class="form-control" id="biografi" name="biografi" rows="4"><?= esc($nilai('biografi')) ?></textarea>
            </div>
        </div>
        <?php endif ?>

        <?php if (! $langsung) : ?>
        <div class="form-section">Catatan untuk Verifikator</div>
        <textarea class="form-control" name="catatan_pengusul" rows="2" placeholder="Sumber informasi, mis. buku tarombo keluarga, keterangan orang tua…"><?= esc(old('catatan_pengusul')) ?></textarea>
        <?php endif ?>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a class="btn btn-light" href="<?= $kembali ?>">Batal</a>
            <button class="btn btn-utama px-4" type="submit"><?= $langsung ? 'Simpan' : 'Kirim usulan' ?></button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/form-anggota.js') ?>"></script>
<?= $this->endSection() ?>
