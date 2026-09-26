<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Sejarah Marga<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 860px">
    <h1 class="h3 mb-1">Sejarah marga <?= esc($marga['nama']) ?></h1>
    <p class="text-teks-2">Tampil di halaman <a href="<?= site_url('kenali-marga') ?>">Kenali Marga</a> dan di awal "Jalur saya" setiap anggota.</p>
    <form method="post" class="card card-body p-4">
        <?= csrf_field() ?>
        <label class="form-label" for="asal_kampung">Bona pasogit (asal kampung)</label>
        <input class="form-control mb-3" id="asal_kampung" name="asal_kampung" value="<?= esc(old('asal_kampung', $marga['asal_kampung'] ?? '')) ?>" maxlength="255">
        <label class="form-label" for="sejarah">Kisah marga</label>
        <textarea class="form-control mb-1" id="sejarah" name="sejarah" rows="10"><?= esc(old('sejarah', $marga['sejarah'] ?? '')) ?></textarea>
        <div class="form-text mb-4">Pisahkan paragraf dengan baris kosong. Format: **tebal**, *miring*, - daftar.</div>

        <div class="form-section">Leluhur sebelum marga (informasi sejarah, tidak dihitung sundut)</div>
        <p class="small text-teks-2">Urutkan dari yang paling tua. Nama-nama ini tampil dengan garis putus-putus sebelum Sundut 1.</p>
        <div id="praMarga">
            <?php foreach (array_pad($praMarga, max(count($praMarga) + 1, 5), ['nama' => '', 'keterangan' => '']) as $i => $pm) : ?>
                <div class="row g-2 mb-2">
                    <div class="col-auto"><span class="chip"><?= $i + 1 ?></span></div>
                    <div class="col-md-4"><input class="form-control form-control-sm" name="pra_nama[]" value="<?= esc($pm['nama'], 'attr') ?>" maxlength="150" placeholder="Nama leluhur" aria-label="Nama leluhur <?= $i + 1 ?>"></div>
                    <div class="col"><input class="form-control form-control-sm" name="pra_ket[]" value="<?= esc($pm['keterangan'] ?? '', 'attr') ?>" maxlength="300" placeholder="Keterangan singkat" aria-label="Keterangan <?= $i + 1 ?>"></div>
                </div>
            <?php endforeach ?>
        </div>
        <div class="text-end mt-3"><button class="btn btn-utama px-4">Simpan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
