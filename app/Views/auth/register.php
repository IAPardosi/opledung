<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('title') ?>Daftar Member<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container py-4" style="max-width: 1000px">
    <div class="row g-0 card overflow-hidden flex-row" style="border-radius: 32px">
        <div class="col-md-5 kartu-gelap p-4 p-lg-5">
            <div class="horas" style="color:var(--merah-terang);font-weight:700;letter-spacing:.12em;font-size:.8rem;text-transform:uppercase">Kepala keluarga</div>
            <h1 class="h3 mt-2">Daftarkan keluarga Anda</h1>
            <p class="small" style="color:#e2d6cb">Pendaftaran terdiri dari tiga langkah:</p>
            <ol class="small ps-3" style="color:#e2d6cb">
                <li class="mb-2"><b class="text-white">Buat akun</b> di halaman ini.</li>
                <li class="mb-2"><b class="text-white">Isi silsilah dan keluarga</b>: pilih leluhur terdekat yang sudah tercatat, lalu istri dan anak-anak.</li>
                <li><b class="text-white">Validasi dua lapis</b>: ayah/ompung atau anak Anda yang sudah menjadi member membenarkan, lalu penatua punguan mengesahkan.</li>
            </ol>
        </div>
        <div class="col-md-7 p-4 p-lg-5">
            <?= view('auth/_pesan') ?>
            <form action="<?= url_to('register') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Nama pengguna</label>
                    <input type="text" class="form-control" id="username" name="username" autocomplete="username" value="<?= old('username') ?>" required placeholder="mis. hotman.pardosi">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="password">Kata sandi</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password_confirm">Ulangi kata sandi</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-utama btn-lg w-100">Buat akun & lanjut isi silsilah keluarga</button>
            </form>
            <p class="small text-center mt-3 mb-0">Sudah punya akun? <a href="<?= url_to('login') ?>">Masuk</a></p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
