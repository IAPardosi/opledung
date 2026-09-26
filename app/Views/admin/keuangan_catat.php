<?php
use App\Models\KeuanganCatatanModel;

$lama = static fn (string $k, $default = '') => old($k) ?? $default;
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Catat Pembayaran<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 760px">
    <a href="<?= site_url('admin/keuangan?punguan=' . $punguan['id']) ?>" class="small"><i class="bi bi-arrow-left"></i> Keuangan <?= esc($punguan['nama']) ?></a>
    <h1 class="h3 mt-2 mb-1">Catat pembayaran</h1>
    <p class="text-teks-2">Hanya <b>Member Punguan aktif</b> <?= esc($punguan['nama']) ?> yang dapat dicatat. Member marga yang bukan anggota punguan tidak memiliki kewajiban punguan.
        <?= auth()->user()->can('keuangan.validasi') ? 'Catatan Anda (Penatua) langsung sah.' : 'Catatan akan divalidasi Penatua punguan.' ?></p>

    <?php if ($anggota === []) : ?>
        <div class="alert alert-warning">Belum ada Member Punguan aktif. <a href="<?= site_url('admin/punguan-anggota?punguan=' . $punguan['id']) ?>">Daftarkan member punguan</a> terlebih dahulu.</div>
    <?php elseif ($kategori === []) : ?>
        <div class="alert alert-warning">Belum ada kategori keuangan. <a href="<?= site_url('admin/keuangan/kategori?punguan=' . $punguan['id']) ?>">Atur kategori</a> terlebih dahulu.</div>
    <?php else : ?>
    <form method="post" class="card card-body p-4">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold" for="keanggotaan_id">Member punguan</label>
                <select name="keanggotaan_id" id="keanggotaan_id" class="form-select" required>
                    <option value="">— Pilih anggota —</option>
                    <?php foreach ($anggota as $a) : ?>
                        <option value="<?= $a['id'] ?>" <?= (int) $lama('keanggotaan_id', $pilih) === (int) $a['id'] ? 'selected' : '' ?>><?= esc($a['nama_lengkap']) ?> · <?= esc($a['nomor_anggota'] ?? $a['kode_anggota']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="kategori_id">Kategori</label>
                <select name="kategori_id" id="kategori_id" class="form-select" required>
                    <?php foreach ($kategori as $k) : ?>
                        <option value="<?= $k['id'] ?>" data-jenis="<?= esc($k['jenis']) ?>" data-nominal="<?= esc((string) $k['nominal_standar']) ?>" <?= (int) $lama('kategori_id') === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="nominal">Nominal (Rp)</label>
                <input type="text" inputmode="numeric" name="nominal" id="nominal" class="form-control" required value="<?= esc($lama('nominal')) ?>" placeholder="25000">
                <div class="form-text" id="infoNominal"></div>
            </div>
            <div class="col-md-6 periode periode-bulanan">
                <label class="form-label fw-semibold" for="periode_bulan">Periode (bulan)</label>
                <input type="month" name="periode" id="periode_bulan" class="form-control" value="<?= esc($lama('periode', date('Y-m'))) ?>">
            </div>
            <div class="col-md-6 periode periode-bulanan">
                <label class="form-label fw-semibold" for="periode_sampai">Sampai bulan <span class="text-teks-3 fw-normal">(opsional, bayar beberapa bulan)</span></label>
                <input type="month" name="periode_sampai" id="periode_sampai" class="form-control" value="<?= esc($lama('periode_sampai')) ?>">
            </div>
            <div class="col-md-6 periode periode-tahunan">
                <label class="form-label fw-semibold" for="periode_tahun">Periode (tahun)</label>
                <input type="number" min="2000" max="2100" name="periode" id="periode_tahun" class="form-control" value="<?= esc($lama('periode', date('Y'))) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="tanggal">Tanggal bayar</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required max="<?= date('Y-m-d') ?>" value="<?= esc($lama('tanggal', date('Y-m-d'))) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="metode">Cara bayar</label>
                <select name="metode" id="metode" class="form-select">
                    <?php foreach (KeuanganCatatanModel::METODE as $k => $v) : ?><option value="<?= $k ?>" <?= $lama('metode') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="keterangan">Keterangan</label>
                <input type="text" name="keterangan" id="keterangan" class="form-control" maxlength="255" value="<?= esc($lama('keterangan')) ?>" placeholder="mis. Hamauliateon pesta pernikahan anak, Toktok ripe pembangunan tugu">
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-utama"><i class="bi bi-save"></i> Simpan catatan</button>
            <a href="<?= site_url('admin/keuangan?punguan=' . $punguan['id']) ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
(function () {
    const kat = document.getElementById('kategori_id');
    if (!kat) return;
    const nominal = document.getElementById('nominal');
    const info = document.getElementById('infoNominal');
    function atur(awal) {
        const o = kat.selectedOptions[0];
        const jenis = o.dataset.jenis;
        document.querySelectorAll('.periode').forEach((el) => {
            const tampil = el.classList.contains('periode-' + jenis);
            el.classList.toggle('d-none', !tampil);
            el.querySelectorAll('input').forEach((i) => { i.disabled = !tampil; });
        });
        info.textContent = o.dataset.nominal ? 'Nominal standar: Rp' + Number(o.dataset.nominal).toLocaleString('id-ID') + (jenis === 'bulanan' ? ' per bulan' : '') : '';
        if (!awal || nominal.value === '') nominal.value = o.dataset.nominal || '';
    }
    kat.addEventListener('change', () => atur(false));
    atur(true);
})();
</script>
<?= $this->endSection() ?>
