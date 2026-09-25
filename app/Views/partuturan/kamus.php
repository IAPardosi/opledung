<?php use App\Controllers\Partuturan; ?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kamus Partuturan<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <div class="row g-4 align-items-center mb-4">
        <div class="col-lg-8">
            <p class="eyebrow mb-1">Tutur sapa Batak Toba</p>
            <h1 class="h2">Kamus Partuturan</h1>
            <p class="text-teks-2 mb-0">Partuturan menentukan cara memanggil dan bersikap kepada sesama keluarga, berlandaskan <b>Dalihan Na Tolu</b>:
                hula-hula (pihak pemberi istri), dongan tubu (saudara semarga), dan boru (pihak penerima istri).
                Anggota yang sudah terverifikasi dapat melihat partuturannya dengan siapa pun di silsilah melalui <a href="<?= site_url('hubungan') ?>">Cek Partuturan</a>.</p>
        </div>
        <div class="col-lg-4">
            <div class="tutur"><div class="ipon-kecil"></div><div class="isi">
                <div class="label">Dalihan Na Tolu</div>
                <div class="sebutan" style="font-size:1.35rem">Somba marhula-hula, elek marboru, manat mardongan tubu</div>
            </div></div>
        </div>
    </div>

    <?php foreach (Partuturan::KELOMPOK as $kunci => $judul) : ?>
        <?php if (empty($grup[$kunci])) : continue; endif ?>
        <div class="judul-bagian mt-4"><h2 class="h4"><?= esc($judul) ?></h2></div>
        <div class="row g-3">
            <?php foreach ($grup[$kunci] as $i) : ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="kamus-istilah">
                        <div class="nama"><?= esc($i['sebutan']) ?></div>
                        <div class="small text-teks-2"><?= esc($i['keterangan'] ?? '') ?></div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>
    <p class="small text-teks-2 mt-4"><i class="bi bi-info-circle"></i> Istilah dapat berbeda antardaerah; Ketua Adat dapat menyesuaikannya.</p>
</div>
<?= $this->endSection() ?>
