<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('title') ?>Masuk<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container py-4" style="max-width: 960px">
    <div class="row g-0 card overflow-hidden flex-row">
        <div class="col-md-5 pola-gorga text-white p-4 d-flex flex-column justify-content-between" style="min-height: 320px">
            <div>
                <div class="horas" style="font-family:var(--font-judul);color:#f0a9ad">Horas!</div>
                <h1 class="h3 mt-2">Masuk ke Tarombo</h1>
                <p class="small" style="color:#e2d6cb">Lihat profil keluarga, partuturan Anda dengan sesama anggota, dan ikut memberi kesaksian silsilah.</p>
            </div>
            <img src="<?= base_url('assets/img/rumah-bolon.svg') ?>" alt="" class="img-fluid mt-3" style="max-width: 240px">
        </div>
        <div class="col-md-7 p-4 p-lg-5">
            <?= view('auth/_pesan') ?>
            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Kata sandi</label>
                    <input type="password" class="form-control form-control-lg" id="password" name="password" autocomplete="current-password" required>
                </div>
                <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember" <?= old('remember') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                <?php endif ?>
                <button type="submit" class="btn btn-utama btn-lg w-100">Masuk</button>
            </form>
            <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                <p class="small text-center mt-3 mb-0">Lupa kata sandi? <a href="<?= url_to('magic-link') ?>">Masuk lewat tautan email</a></p>
            <?php endif ?>
            <?php if (setting('Auth.allowRegistration')) : ?>
                <p class="small text-center mt-2 mb-0">Belum punya akun? <a href="<?= url_to('register') ?>">Daftar sebagai member</a></p>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
