<?php
/**
 * Kotak keluarga: orang ini, pasangannya (istri boleh lebih dari satu), dan anak dikelompokkan per ibu.
 * Warna + bentuk: ■ anak (garis utama, merah), ◆ boru (emas), ● anak boru, ○ pasangan.
 *
 * @var \App\Entities\Person                 $p
 * @var list<array<string, mixed>>           $pasangan
 * @var list<\App\Entities\Person>           $anak
 * @var bool                                 $bolehPasangan
 * @var bool                                 $bolehAnak
 * @var bool                                 $langsung      tambah langsung (bukan usulan)
 */
$istri   = $p->jenis_kelamin === 'L';
$banyak  = count($pasangan) > 1;
$kelompok = [];
if ($istri && $banyak) {
    foreach ($pasangan as $ps) {
        $kelompok[(int) $ps['id']] = ['judul' => 'Dari Istri ' . $ps['pernikahan_ke'] . ' · ' . $ps['nama_lengkap'], 'anak' => []];
    }
    $kelompok[0] = ['judul' => 'Ibu belum tercatat', 'anak' => []];
    foreach ($anak as $a) {
        $kelompok[isset($kelompok[(int) $a->ibu_id]) ? (int) $a->ibu_id : 0]['anak'][] = $a;
    }
    $kelompok = array_filter($kelompok, static fn ($k) => $k['anak'] !== []);
} else {
    $kelompok[] = ['judul' => null, 'anak' => $anak];
}
$bentuk = ['utama' => 'penanda-utama', 'boru' => 'penanda-boru', 'anak_boru' => 'penanda-anak_boru'];
?>
<section class="card kotak-keluarga mb-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span>Kotak keluarga</span>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($bolehPasangan) : ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('anggota/' . $p->id . '/tambah-pasangan') ?>"><i class="bi bi-plus-lg"></i> <?= $langsung ? 'Tambah' : 'Usulkan' ?> <?= $istri ? 'istri' : 'suami' ?></a>
            <?php endif ?>
            <?php if ($bolehAnak) : ?>
                <a class="btn btn-sm btn-emas" href="<?= site_url('anggota/' . $p->id . '/tambah-anak') ?>"><i class="bi bi-plus-lg"></i> <?= $langsung ? 'Tambah' : 'Usulkan' ?> anak</a>
            <?php endif ?>
        </div>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="penanda <?= $bentuk[$p->garis] ?? 'penanda-pasangan' ?>" aria-hidden="true"></span>
            <div>
                <div class="fw-bold"><?= esc($p->nama_lengkap) ?></div>
                <div class="small text-teks-2">Sundut <?= $p->generasi_ke ?> · <?= esc(label_garis($p->garis)) ?><?= $p->huta ? ' · ' . esc($p->huta) : '' ?></div>
            </div>
        </div>

        <?php if ($p->garis !== 'anak_boru') : ?>
        <div class="kotak-pasangan">
            <?php foreach ($pasangan as $ps) : ?>
                <a class="pasangan" href="<?= site_url('anggota/' . $ps['id']) ?>">
                    <span class="penanda penanda-pasangan" aria-hidden="true"></span>
                    <span>
                        <span class="peran"><?= $istri ? ($banyak ? 'Istri ' . (int) $ps['pernikahan_ke'] : 'Istri') : 'Suami' ?><?= $ps['status_pernikahan'] === 'cerai_hidup' ? ' · bercerai' : '' ?></span>
                        <span class="d-block fw-semibold"><?= esc($ps['nama_lengkap']) ?><?= $ps['status_hidup'] === 'meninggal' ? ' †' : '' ?></span>
                        <?php if ($ps['marga_nama']) : ?><span class="d-block small text-teks-2">Marga <?= esc($ps['marga_nama']) ?></span><?php endif ?>
                    </span>
                </a>
            <?php endforeach ?>
            <?php if ($pasangan === []) : ?><div class="small text-teks-2"><?= $istri ? 'Istri' : 'Suami' ?> belum tercatat.</div><?php endif ?>
        </div>
        <?php endif ?>

        <?php if ($p->bisaPunyaAnak()) : ?>
            <?php foreach ($anak === [] ? [] : $kelompok as $k) : ?>
                <div>
                    <div class="label-baris mb-2"><?= $k['judul'] ? esc($k['judul']) : 'Anak (' . count($anak) . ')' ?></div>
                    <div class="kotak-anak">
                        <?php foreach ($k['anak'] as $a) : ?>
                            <a class="anak garis-kotak-<?= esc($a->garis, 'attr') ?>" href="<?= site_url('anggota/' . $a->id) ?>">
                                <span class="jenis"><span class="penanda <?= $bentuk[$a->garis] ?? '' ?>" aria-hidden="true"></span><?= $a->garis === 'utama' ? 'Anak' : ($a->garis === 'boru' ? 'Boru' : 'Anak boru') ?></span>
                                <span class="fw-bold"><?= esc($a->nama_lengkap) ?></span>
                                <span class="small text-teks-2">Sundut <?= $a->generasi_ke ?><?= lahir_wafat($a) ? ' · ' . esc(lahir_wafat($a)) : '' ?></span>
                            </a>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endforeach ?>
            <?php if ($anak === []) : ?><div class="small text-teks-2">Anak belum tercatat.</div><?php endif ?>
            <?php if ($p->garis === 'boru') : ?><div class="small text-teks-2">Anak dari boru dicatat sampai di sini dan tidak diteruskan.</div><?php endif ?>
        <?php endif ?>
    </div>
</section>
