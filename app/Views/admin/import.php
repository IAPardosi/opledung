<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Import Data Anggota<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container" style="max-width: 1000px">
    <h1 class="h3 mb-1">Import Data Anggota</h1>
    <p class="text-body-secondary">Unggah berkas Excel (.xlsx) atau CSV sesuai template. Semua baris diperiksa terlebih dahulu (pratinjau) sebelum disimpan.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <form method="post" action="<?= site_url('admin/import') ?>" enctype="multipart/form-data" class="card card-body h-100">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="marga_id">Marga tujuan</label>
                    <select class="form-select" id="marga_id" name="marga_id" required>
                        <?php foreach ($margas as $m) : ?>
                            <option value="<?= $m['id'] ?>" <?= (int) ($margaId ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['nama']) ?> (<?= esc($m['kode']) ?>)</option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="berkas">Berkas (.xlsx, .xls, .csv · maks. 10 MB)</label>
                    <input type="file" class="form-control" id="berkas" name="berkas" accept=".xlsx,.xls,.csv" required>
                </div>
                <button class="btn btn-utama mt-auto"><i class="bi bi-search"></i> Periksa (pratinjau)</button>
            </form>
        </div>
        <div class="col-md-5">
            <div class="card card-body h-100 small">
                <div class="fw-semibold mb-2">Aturan singkat</div>
                <ul class="ps-3 mb-3">
                    <li><code>kode_ref</code>: kode sementara per baris (mis. A1, A2).</li>
                    <li><code>kode_induk</code>: <code>kode_ref</code> orang tua di berkas, atau kode anggota yang sudah ada (mis. PDS-G10-000123).</li>
                    <li>Urutkan dari generasi tertua. Induk harus di atas anaknya.</li>
                    <li>Generasi dan garis dihitung otomatis.</li>
                    <li>Generasi 1–10 hanya dapat diimport oleh Ketua Adat.</li>
                </ul>
                <a href="<?= site_url('admin/import/template') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Unduh template</a>
            </div>
        </div>
    </div>

    <?php if ($hasil !== null) : ?>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-semibold">Hasil pratinjau: <?= esc($namaFile) ?></span>
                <span class="badge text-bg-success ms-2"><?= $hasil['jumlah_ok'] ?> siap</span>
                <?php if ($hasil['jumlah_gagal'] > 0) : ?><span class="badge text-bg-danger"><?= $hasil['jumlah_gagal'] ?> bermasalah</span><?php endif ?>
            </div>
            <?php if ($hasil['berhasil']) : ?>
                <form method="post" action="<?= site_url('admin/import/simpan') ?>" onsubmit="this.querySelector('button').disabled=true">
                    <?= csrf_field() ?>
                    <button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Simpan <?= $hasil['jumlah_ok'] ?> baris</button>
                </form>
            <?php else : ?>
                <span class="small text-danger">Perbaiki baris bermasalah lalu unggah ulang. Tidak ada data yang disimpan.</span>
            <?php endif ?>
        </div>
        <div class="table-responsive" style="max-height: 60vh">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light sticky-top"><tr><th>Baris</th><th>kode_ref</th><th>Nama</th><th>Hasil</th></tr></thead>
                <tbody>
                <?php foreach ($hasil['laporan'] as $r) : ?>
                    <tr class="<?= $r['ok'] ? '' : 'table-danger' ?>">
                        <td><?= $r['baris'] ?></td>
                        <td class="font-monospace small"><?= esc($r['kode_ref']) ?></td>
                        <td><?= esc($r['nama']) ?></td>
                        <td class="small"><?= $r['ok'] ? '<i class="bi bi-check-circle text-success"></i> Generasi ' . $r['generasi'] : esc($r['pesan']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
