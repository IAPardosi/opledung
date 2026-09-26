<?php
/**
 * Pilihan validator keluarga untuk sebuah usulan.
 *
 * @var list<array<string, mixed>> $kandidat
 */
$saya = auth()->id();
$diri = in_array($saya, array_column($kandidat, 'user_id'), true);
?>
<div class="form-section">Validasi keluarga</div>
<?php if ($diri) : ?>
    <div class="alert alert-light border small mb-0"><i class="bi bi-patch-check text-success"></i> Anda keluarga dalam garis langsung, jadi validasi keluarga dianggap terpenuhi. Usulan langsung diteruskan ke penatua punguan.</div>
<?php elseif ($kandidat === []) : ?>
    <div class="alert alert-light border small mb-0"><i class="bi bi-info-circle"></i> Belum ada anggota sah dalam garis langsung (orang tua/ompung atau anak/pahompu) yang bisa memvalidasi. Penatua punguan akan memeriksa usulan ini lebih teliti.</div>
<?php else : ?>
    <p class="small text-teks-2 mb-2">Pilih satu anggota keluarga dalam garis langsung (paling jauh 2 sundut ke atas atau ke bawah) yang akan memastikan data ini benar.</p>
    <div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(230px, 1fr))">
        <?php foreach ($kandidat as $i => $k) : ?>
            <label class="kartu-orang" for="validator_<?= $k['user_id'] ?>" style="cursor: pointer">
                <input class="form-check-input mt-0" type="radio" name="validator_user_id" id="validator_<?= $k['user_id'] ?>" value="<?= $k['user_id'] ?>" <?= (string) old('validator_user_id', $i === 0 ? $k['user_id'] : '') === (string) $k['user_id'] ? 'checked' : '' ?> required>
                <span><span class="n d-block"><?= esc($k['nama_lengkap']) ?></span><span class="k"><?= esc($k['hubungan']) ?> · Sundut <?= $k['generasi_ke'] ?> · @<?= esc($k['username']) ?></span></span>
            </label>
        <?php endforeach ?>
    </div>
<?php endif ?>
