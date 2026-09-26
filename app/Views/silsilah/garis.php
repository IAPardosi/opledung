<?php
/**
 * @var list<\App\Entities\Person> $jalur
 * @var \App\Entities\Person       $person
 */
$n       = count($jalur);
$lipat   = $n > 7; // ringkas: 3 sundut pertama + 2 terakhir
$awal    = $jalur[0] ?? null;
$melalui = array_slice(array_map(static fn ($p) => $p->gelar_adat ?: $p->nama_lengkap, $jalur), 1, 2);
$subjek  = $diri ? 'Anda' : esc($person->nama_lengkap);
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Jalur <?= esc($person->nama_lengkap) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 980px">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <?= view('partials/mode_tampil', ['aktif' => 'garis', 'id' => $diri ? null : $person->id]) ?>
        <?php if ($lipat) : ?>
            <div class="mode-tampil" role="group" aria-label="Kerapatan">
                <a href="#" data-kerapatan="ringkas" class="active">Ringkas</a>
                <a href="#" data-kerapatan="lengkap">Lengkap</a>
            </div>
        <?php endif ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <p class="eyebrow mb-1">Garis keturunan</p>
            <h1 class="h2 mb-3"><?= $diri ? 'Jalur Anda' : esc($person->nama_lengkap) ?></h1>
            <p class="fs-5 mb-3" style="line-height:1.5">
                <?= $subjek ?> adalah <b>sundut ke-<?= $person->generasi_ke ?></b> dari <?= esc($awal?->gelar_adat ?: $awal?->nama_lengkap ?? '') ?><?= $melalui ? ', melalui ' . esc(implode(' dan ', $melalui)) : '' ?>.
            </p>
            <p class="text-teks-2">Setiap sundut adalah satu generasi. Garis ini adalah jalur lurus dari leluhur awal marga <?= esc($marga['nama'] ?? '') ?> sampai <?= $diri ? 'Anda' : 'beliau' ?>; saudara di setiap sundut ditunjukkan dengan angka.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-gelap" href="<?= site_url('keluarga-dekat' . ($diri ? '' : '/' . $person->id)) ?>"><i class="bi bi-people"></i> Keluarga dekat</a>
                <a class="btn btn-outline-secondary" href="<?= site_url('kenali-marga') ?>"><i class="bi bi-book"></i> Kisah marga</a>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card card-body p-4">
                <div class="linimasa" id="linimasa">
                    <?php if ($praMarga !== []) : ?>
                        <div class="simpul"><span class="nomor pra"><i class="bi bi-three-dots"></i></span>
                            <div class="isi"><div class="nama text-teks-2">Sebelum <?= esc($marga['nama'] ?? 'marga') ?></div>
                                <div class="ket"><?= esc(implode(' → ', array_column($praMarga, 'nama'))) ?> · <?= count($praMarga) ?> generasi, informasi sejarah</div></div></div>
                        <div class="sambung putus"></div>
                    <?php endif ?>
                    <?php foreach ($jalur as $i => $j) : ?>
                        <?php
                        $tengah = $lipat && $i >= 3 && $i < $n - 2;
                        $ini    = $j->id === $person->id;
                        ?>
                        <?php if ($lipat && $i === 3) : ?>
                            <div class="bagian-ringkas"><div class="simpul"><span class="nomor lipat">+<?= $n - 5 ?></span><div class="isi"><button type="button" class="btn btn-sm btn-outline-secondary" data-kerapatan="lengkap">Tampilkan Sundut <?= $jalur[3]->generasi_ke ?>–<?= $jalur[$n - 3]->generasi_ke ?></button></div></div><div class="sambung"></div></div>
                        <?php endif ?>
                        <div class="<?= $tengah ? 'bagian-tengah d-none' : '' ?>">
                            <div class="simpul">
                                <span class="nomor<?= $ini ? ' saya' : '' ?>"><?= $j->generasi_ke ?></span>
                                <div class="isi">
                                    <a href="<?= site_url('anggota/' . $j->id) ?>" class="nama text-body"><?= esc($j->nama_lengkap) ?></a><?php if ($j->gelar_adat) : ?> <span class="small text-teks-2">· <?= esc($j->gelar_adat) ?></span><?php endif ?>
                                    <div class="ket">
                                        <?= esc(trim(implode(' · ', array_filter([
                                            $ini ? ($diri ? 'Anda' : null) : null,
                                            lahir_wafat($j),
                                            ($saudara[$j->id] ?? 0) > 0 ? ($saudara[$j->id] . ' saudara') : null,
                                        ])))) ?>
                                    </div>
                                    <?php if ($j->biografi && ! $ini) : ?><div class="small text-teks-2 mt-1"><?= esc(mb_strimwidth($j->biografi, 0, 140, '…')) ?></div><?php endif ?>
                                </div>
                            </div>
                            <?php if ($i < $n - 1) : ?><div class="sambung<?= $i === $n - 2 ? ' merah' : '' ?>"></div><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.querySelectorAll('[data-kerapatan]').forEach((el) => el.addEventListener('click', (e) => {
    e.preventDefault();
    const lengkap = el.dataset.kerapatan === 'lengkap';
    document.querySelectorAll('.bagian-tengah').forEach((b) => b.classList.toggle('d-none', !lengkap));
    document.querySelectorAll('.bagian-ringkas').forEach((b) => b.classList.toggle('d-none', lengkap));
    document.querySelectorAll('a[data-kerapatan]').forEach((a) => a.classList.toggle('active', a.dataset.kerapatan === el.dataset.kerapatan));
}));
</script>
<?= $this->endSection() ?>
