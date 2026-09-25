<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Istilah Partuturan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 1000px">
    <h1 class="h3 mb-1">Istilah Partuturan</h1>
    <p class="text-teks-2">Sesuaikan sebutan dengan adat setempat. Aturan kapan sebuah istilah dipakai (mis. <i>tulang</i> = saudara laki-laki ibu) ditentukan oleh sistem;
        yang diubah di sini adalah sebutan dan keterangannya. Perlu istilah atau aturan baru? Sampaikan kepada pengembang.</p>
    <form method="post" class="card">
        <?= csrf_field() ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th style="width:18%">Kunci sistem</th><th style="width:27%">Sebutan</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($kelompok as $k => $judul) : ?>
                    <?php if (empty($grup[$k])) : continue; endif ?>
                    <tr class="table-light"><td colspan="3" class="small fw-bold text-utama text-uppercase"><?= esc($judul) ?></td></tr>
                    <?php foreach ($grup[$k] as $r) : ?>
                        <tr>
                            <td class="small font-monospace text-teks-2"><?= esc($r['kunci']) ?></td>
                            <td><input class="form-control form-control-sm fw-semibold" name="sebutan[<?= esc($r['kunci'], 'attr') ?>]" value="<?= esc($r['sebutan'], 'attr') ?>" maxlength="100" required></td>
                            <td><input class="form-control form-control-sm" name="keterangan[<?= esc($r['kunci'], 'attr') ?>]" value="<?= esc($r['keterangan'] ?? '', 'attr') ?>" maxlength="255"></td>
                        </tr>
                    <?php endforeach ?>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-end"><button class="btn btn-utama px-4">Simpan perubahan</button></div>
    </form>
</div>
<?= $this->endSection() ?>
