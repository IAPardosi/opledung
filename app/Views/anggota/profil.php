<?php
/**
 * @var \App\Entities\Person $person
 */
$p        = $person;
$cfg      = config('Silsilah');
$labelJk  = $cfg->jenisKelamin[$p->jenis_kelamin] ?? $p->jenis_kelamin;
$statusDataBadge = [
    'draft'         => '<span class="badge text-bg-light border">Belum diverifikasi</span>',
    'terverifikasi' => '<span class="badge text-bg-success">Terverifikasi</span>',
    'terkunci'      => '<span class="badge text-bg-dark"><i class="bi bi-lock-fill"></i> Silsilah Pokok · terkunci</span>',
][$p->status_data];
$baris = static function (string $label, ?string $nilai): string {
    return $nilai === null || $nilai === '' ? '' : '<dt class="col-sm-4">' . esc($label) . '</dt><dd class="col-sm-8">' . esc($nilai) . '</dd>';
};
$orangKecil = static function ($o, ?string $ket = null): string {
    $o = $o instanceof \App\Entities\Person ? $o->toRawArray() : $o;
    return '<a href="' . site_url('anggota/' . $o['id']) . '" class="list-group-item list-group-item-action">'
        . '<span class="avatar avatar-sm garis-' . esc($o['garis'], 'attr') . '">' . esc(inisial($o['nama_lengkap'])) . '</span>'
        . '<span class="flex-grow-1"><span class="d-block fw-medium">' . esc($o['nama_lengkap']) . '</span>'
        . '<span class="small text-body-secondary">' . esc(trim(($ket ? $ket . ' · ' : '') . lahir_wafat($o), ' ·')) . '</span></span>'
        . '<span class="small text-body-secondary font-monospace d-none d-md-inline">' . esc($o['kode_anggota']) . '</span></a>';
};
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($p->nama_lengkap) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container">
    <?php if ($jalur !== []) : ?>
    <nav class="jalur mb-3" aria-label="Jalur leluhur">
        <?php foreach ($jalur as $i => $j) : ?>
            <?php if ($i > 0) : ?><span class="panah"><i class="bi bi-chevron-right"></i></span><?php endif ?>
            <?php if ($j->id === $p->id) : ?>
                <span class="aktif">G<?= $j->generasi_ke ?> · <?= esc($j->nama_lengkap) ?></span>
            <?php else : ?>
                <a href="<?= site_url('anggota/' . $j->id) ?>" title="<?= esc($j->nama_lengkap) ?>">G<?= $j->generasi_ke ?> · <?= esc(mb_strimwidth($j->nama_lengkap, 0, 20, '…')) ?></a>
            <?php endif ?>
        <?php endforeach ?>
    </nav>
    <?php endif ?>

    <div class="card mb-4">
        <div class="card-body d-flex flex-column flex-md-row gap-4 align-items-md-center">
            <?php if ($p->foto) : ?>
                <img src="<?= site_url('anggota/' . $p->id . '/foto') ?>" alt="Foto <?= esc($p->nama_lengkap, 'attr') ?>" class="avatar">
            <?php else : ?>
                <span class="avatar garis-<?= esc($p->garis, 'attr') ?>"><?= esc(inisial($p->nama_lengkap)) ?></span>
            <?php endif ?>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap gap-2 mb-1"><?= badge_garis($p->garis) ?> <?= $statusDataBadge ?> <?php if ($diri) : ?><span class="badge text-bg-warning">Profil Anda</span><?php endif ?> <?= $p->garis !== 'anak_boru' ? badge_member($keanggotaan) : '' ?></div>
                <h1 class="h3 mb-0"><?= esc($p->nama_lengkap) ?></h1>
                <?php if ($p->gelar_adat || $p->nama_panggilan) : ?>
                    <div class="text-body-secondary"><?= esc(implode(' · ', array_filter([$p->gelar_adat, $p->nama_panggilan ? 'Panggilan: ' . $p->nama_panggilan : null]))) ?></div>
                <?php endif ?>
                <div class="small mt-2 d-flex flex-wrap gap-3 text-body-secondary">
                    <span><i class="bi bi-upc"></i> <span class="font-monospace"><?= esc($p->kode_anggota) ?></span></span>
                    <span><i class="bi bi-layers"></i> Generasi <?= $p->generasi_ke ?></span>
                    <?php if (in_array($p->garis, ['pasangan', 'anak_boru'], true) && $p->marga_nama) : ?><span><i class="bi bi-bookmark"></i> Marga <?= esc($p->marga_nama) ?></span><?php endif ?>
                    <?php if ($p->isAnggotaGarisMarga()) : ?><span><i class="bi bi-diagram-3"></i> <?= number_format($jumlah_keturunan, 0, ',', '.') ?> keturunan tercatat</span><?php endif ?>
                    <?php if ($akunTertaut) : ?><span><i class="bi bi-person-check"></i> Terdaftar sebagai member</span><?php endif ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-self-md-start">
                <?php if ($p->garis !== 'pasangan') : ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('silsilah/' . $p->id) ?>"><i class="bi bi-diagram-3"></i> Lihat di pohon</a>
                <?php endif ?>
                <?php if ($ubahLangsung || $bolehUsul) : ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('anggota/' . $p->id . '/ubah') ?>"><i class="bi bi-pencil"></i> <?= $ubahLangsung ? 'Ubah data' : 'Usulkan perubahan' ?></a>
                <?php endif ?>
                <?php if ($bolehValidasi) : ?>
                    <form method="post" action="<?= site_url('anggota/' . $p->id . '/validasi') ?>" onsubmit="return confirm('<?= $pokok ? 'Validasi dan kunci data ini sebagai Silsilah Pokok?' : 'Tandai data ini terverifikasi?' ?>')">
                        <?= csrf_field() ?>
                        <button class="btn btn-success btn-sm"><i class="bi bi-patch-check"></i> <?= $pokok ? 'Validasi & kunci' : 'Verifikasi' ?></button>
                    </form>
                <?php endif ?>
                <?php if ($bisaKlaim && ! $klaimPending) : ?>
                    <a class="btn btn-utama btn-sm" href="<?= site_url('pendaftaran/klaim/' . $p->id) ?>"><i class="bi bi-person-raised-hand"></i> Ini saya</a>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($tutur !== null) : ?>
        <div class="mb-4"><?= view('partials/tutur', ['hasil' => $tutur, 'dari' => $saya, 'ke' => $p, 'diri' => true]) ?></div>
    <?php elseif (auth()->user()->person_id === null) : ?>
        <div class="alert alert-light border small mb-4"><i class="bi bi-people text-utama"></i> Tautkan akun Anda ke data silsilah (tombol <b>"Ini saya"</b> pada profil Anda) untuk melihat partuturan Anda dengan anggota ini.</div>
    <?php endif ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <?= view('partials/kotak_keluarga', [
                'p'             => $p,
                'pasangan'      => $pasangan,
                'anak'          => $anak,
                'bolehPasangan' => $p->isAnggotaGarisMarga() && ($kelola || $bolehUsul),
                'bolehAnak'     => $p->bisaPunyaAnak() && ($kelolaAnak || $bolehUsul),
                'langsung'      => $kelola,
            ]) ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold">Data Pribadi</div>
                <div class="card-body">
                    <dl class="row data-profil mb-0">
                        <?= $baris('Jenis kelamin', $labelJk) ?>
                        <?= $baris('Huta (kampung asal)', $p->huta) ?>
                        <?= $baris('Tempat lahir', $p->tempat_lahir) ?>
                        <?= $baris('Tanggal lahir', $p->tanggal_lahir ? tanggal_indo($p->tanggal_lahir) : ($p->tahun_lahir ? 'Tahun ' . $p->tahun_lahir : null)) ?>
                        <?= $baris('Anak ke-', $p->urutan_anak ? (string) $p->urutan_anak : null) ?>
                        <?= $baris('Nama ibu', $ibu ? null : $p->nama_ibu) ?>
                        <?= $baris('Agama', $p->agama) ?>
                        <?= $baris('Status perkawinan', $p->status_perkawinan) ?>
                        <?= $baris('Pendidikan terakhir', $p->pendidikan_terakhir) ?>
                        <?= $baris('Pekerjaan', $p->pekerjaan) ?>
                        <?= $baris('Golongan darah', $p->golongan_darah) ?>
                        <?= $baris('Kewarganegaraan', $p->kewarganegaraan) ?>
                        <?= $baris('Status', $cfg->statusHidup[$p->status_hidup] ?? null) ?>
                        <?php if ($p->status_hidup === 'meninggal') : ?>
                            <?= $baris('Wafat', $p->tanggal_wafat ? tanggal_indo($p->tanggal_wafat) : ($p->tahun_wafat ? 'Tahun ' . $p->tahun_wafat : null)) ?>
                            <?= $baris('Tempat makam', $p->tempat_makam) ?>
                        <?php endif ?>
                        <?php if ($lihatSensitif) : ?>
                            <?= $baris('NIK', $nik) ?>
                            <?= $baris('No. KK', $noKk) ?>
                        <?php endif ?>
                    </dl>
                </div>
            </div>

            <?php if ($p->isHidup()) : ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex justify-content-between">
                    <span>Alamat & Kontak</span>
                    <?php if ($p->sembunyikan_kontak) : ?><span class="small text-body-secondary"><i class="bi bi-eye-slash"></i> Disembunyikan dari member lain</span><?php endif ?>
                </div>
                <div class="card-body">
                    <?php if ($lihatKontak) : ?>
                        <?php
                        $alamat = trim(implode(', ', array_filter([
                            $p->alamat_jalan,
                            ($p->rt || $p->rw) ? 'RT ' . ($p->rt ?: '-') . '/RW ' . ($p->rw ?: '-') : null,
                            $wilayah[$p->desa_kode] ?? null,
                            isset($wilayah[$p->kecamatan_kode]) ? 'Kec. ' . $wilayah[$p->kecamatan_kode] : null,
                            $wilayah[$p->kabupaten_kode] ?? null,
                            $wilayah[$p->provinsi_kode] ?? null,
                            $p->kode_pos,
                        ])));
                        ?>
                        <dl class="row data-profil mb-0">
                            <?= $baris('Alamat', $alamat) ?>
                            <?= $baris('No. HP', $p->no_hp) ?>
                            <?= $baris('Email', $p->email) ?>
                        </dl>
                        <?php if ($alamat === '' && ! $p->no_hp && ! $p->email) : ?><p class="text-body-secondary mb-0">Belum diisi.</p><?php endif ?>
                    <?php else : ?>
                        <p class="text-body-secondary mb-0"><i class="bi bi-lock"></i> Anggota ini memilih menyembunyikan alamat dan kontaknya.</p>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>

            <?php if ($p->biografi) : ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold">Riwayat Singkat</div>
                <div class="card-body"><?= nl2br(esc($p->biografi)) ?></div>
            </div>
            <?php endif ?>
        </div>

        <div class="col-lg-5">
            <?php if ($diri || $keanggotaan !== null || $keuangan !== null) : ?>
            <div class="card mb-4 kartu-member">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> Keanggotaan</span>
                    <?= badge_member($keanggotaan) ?>
                </div>
                <div class="card-body">
                    <?php if ($keanggotaan !== null && $keanggotaan['status'] === 'aktif') : ?>
                        <p class="mb-2"><b>Member Punguan <?= esc($keanggotaan['nama_punguan']) ?></b><?= $keanggotaan['nomor_anggota'] ? ' · No. <span class="font-monospace">' . esc($keanggotaan['nomor_anggota']) . '</span>' : '' ?></p>
                        <p class="small text-teks-2 mb-0">Terdaftar sejak <?= esc(tanggal_indo($keanggotaan['tanggal_masuk'])) ?>. Kewajiban punguan (iuran dan lainnya) berlaku dan dicatat oleh Humas, lalu divalidasi Penatua.</p>
                    <?php elseif ($keanggotaan !== null) : ?>
                        <p class="small mb-0"><i class="bi bi-hourglass-split"></i> Pengajuan Member Punguan <b><?= esc($keanggotaan['nama_punguan']) ?></b> menunggu pengesahan Penatua. Sampai disahkan, statusnya tetap <b>Member Marga</b>.</p>
                    <?php else : ?>
                        <p class="small text-teks-2">Tercatat di silsilah sebagai <b>Member Marga</b>. Belum menjadi anggota punguan mana pun, jadi tidak ada kewajiban punguan.</p>
                        <?php if ($pilihPunguan !== [] && $p->isHidup()) : ?>
                            <form method="post" action="<?= site_url('profil-saya/punguan') ?>" class="d-flex gap-2 flex-wrap">
                                <?= csrf_field() ?>
                                <select name="punguan_id" class="form-select form-select-sm" style="max-width: 220px" aria-label="Punguan" required>
                                    <?php foreach ($pilihPunguan as $pg) : ?><option value="<?= $pg['id'] ?>" <?= (int) $pg['id'] === (int) auth()->user()->punguan_id ? 'selected' : '' ?>><?= esc($pg['nama']) ?></option><?php endforeach ?>
                                </select>
                                <button class="btn btn-sm btn-utama">Ajukan jadi Member Punguan</button>
                            </form>
                        <?php endif ?>
                    <?php endif ?>
                </div>
                <?php if ($keuangan !== null && ($keanggotaan !== null || $keuangan !== [])) : ?>
                    <div class="card-header fw-semibold border-top"><i class="bi bi-wallet2"></i> <?= $diri ? 'Keuangan saya' : 'Catatan keuangan' ?></div>
                    <ul class="list-group list-group-flush small">
                        <?php if ($keuangan === []) : ?><li class="list-group-item text-teks-2">Belum ada catatan.</li><?php endif ?>
                        <?php foreach (array_slice($keuangan, 0, 8) as $c) : ?>
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span><?= esc($c['kategori']) ?> <span class="text-teks-3"><?= esc(\App\Services\KeuanganService::labelPeriode($c['periode']) !== '–' ? \App\Services\KeuanganService::labelPeriode($c['periode']) : tanggal_indo($c['tanggal'])) ?></span></span>
                                <span class="text-nowrap"><?= rupiah($c['nominal']) ?> <?= ['sah' => '<i class="bi bi-check-circle-fill text-success" title="Sah"></i>', 'menunggu' => '<i class="bi bi-hourglass-split text-warning" title="Menunggu validasi"></i>', 'ditolak' => '<i class="bi bi-x-circle-fill text-danger" title="Ditolak"></i>'][$c['status']] ?></span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
            <?php endif ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold">Orang Tua</div>
                <div class="list-group list-group-flush daftar-orang">
                    <?= $ayah ? $orangKecil($ayah, 'Ayah') : '' ?>
                    <?= $ibu ? $orangKecil($ibu, 'Ibu') : '' ?>
                    <?php if (! $ayah && ! $ibu) : ?><div class="list-group-item text-body-secondary small"><?= $p->generasi_ke === 1 && $p->garis === 'utama' ? 'Leluhur awal marga.' : 'Tidak tercatat.' ?></div><?php endif ?>
                </div>
            </div>

            <?php if ($saudara !== []) : ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold">Saudara Kandung (<?= count($saudara) ?>)</div>
                <div class="list-group list-group-flush daftar-orang">
                    <?php foreach ($saudara as $s) : ?><?= $orangKecil($s, 'Anak ke-' . ($s->urutan_anak ?? '?')) ?><?php endforeach ?>
                </div>
            </div>
            <?php endif ?>

            <?php if ($kelola && $anak === [] && ! $p->isTerkunci()) : ?>
                <form method="post" action="<?= site_url('anggota/' . $p->id . '/hapus') ?>" class="text-end" onsubmit="return confirm('Hapus data <?= esc($p->nama_lengkap, 'js') ?>? Data diarsipkan dan tidak tampil lagi.')">
                    <?= csrf_field() ?>
                    <button class="btn btn-link btn-sm text-danger"><i class="bi bi-trash"></i> Hapus data ini</button>
                </form>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
