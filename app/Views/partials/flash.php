<?php
// Halaman login/daftar milik Shield sudah menampilkan pesan 'error'/'message' sendiri.
$halamanAuth = in_array(trim(service('uri')->getPath(), '/'), ['login', 'register'], true);
$jenis       = ['sukses' => 'success', 'info' => 'info', 'galat' => 'danger'];
if (! $halamanAuth) {
    $jenis += ['message' => 'success', 'error' => 'danger'];
}
?>
<?php foreach ($jenis as $kunci => $warna) : ?>
    <?php if (is_string(session()->getFlashdata($kunci)) && session()->getFlashdata($kunci) !== '') : ?>
        <div class="alert alert-<?= $warna ?> alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata($kunci)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif ?>
<?php endforeach ?>
<?php if (is_array(session()->getFlashdata('kesalahan'))) : ?>
    <div class="alert alert-danger">
        <strong>Periksa kembali isian berikut:</strong>
        <ul class="mb-0"><?php foreach (session()->getFlashdata('kesalahan') as $e) : ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
    </div>
<?php endif ?>
