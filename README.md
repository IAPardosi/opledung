# Silsilah Marga — Opledung

Aplikasi web silsilah marga berbasis **CodeIgniter 4**. Aplikasi ini dirancang untuk skala besar (18+ generasi, puluhan ribu anggota), dimulai dari marga **Pardosi (Op. Ledung)**, dan dapat dipakai untuk marga lain.

Standar pengembangan ada di [`docs/STANDAR.md`](docs/STANDAR.md). Baca dokumen itu sebelum mengubah kode.

## Kebutuhan

- PHP 8.2+ dengan ekstensi `intl`, `mbstring`, `mysqli`, `sodium`, `gd`, `zip`
- MySQL 8 / MariaDB 10.6+
- Composer

## Instalasi

```bash
composer install
cp env .env                 # lalu isi database.default.* dan app.baseURL
php spark key:generate      # wajib: kunci enkripsi NIK/No. KK
php spark migrate --all     # tabel aplikasi + Shield (login)
php spark db:seed DatabaseSeeder
```

`DatabaseSeeder` mengisi:
- 91.599 data wilayah Indonesia (Kepmendagri 2025: provinsi s.d. desa/kelurahan),
- marga Pardosi (kode `PDS`, batas Silsilah Pokok = generasi 10),
- akun Super Admin. Password acak ditampilkan sekali di terminal, atau atur `superadmin.email` / `superadmin.password` di `.env`.

### Data contoh (development saja)

```bash
php spark db:seed DemoSilsilahSeeder
```

Seeder ini membuat sekitar 4.500 orang **fiktif** dalam 14 generasi, lengkap dengan boru, pasangan, dan anak boru, serta contoh berita, kegiatan, satu pendaftaran yang menunggu validasi, dan Punguan Medan dengan 11 Member Punguan, 4 kategori keuangan, iuran tahun berjalan, dan catatan yang menunggu validasi Penatua. Huta diisi per cabang, dan beberapa keluarga beristri dua (termasuk leluhur `member@`) untuk mencoba kotak keluarga.

Akun demo (password `Demo#12345`), semuanya `@silsilah.local`:

| Akun | Role |
|---|---|
| `ketuaadat@` | Ketua Adat (Silsilah Pokok, partuturan, sejarah marga) |
| `verifikator@` | Admin Marga |
| `penatua@` | Penatua Punguan Medan: lapis 2 pendaftaran, sahkan member punguan, validasi keuangan |
| `humas@` | Humas Punguan Medan: berita & kegiatan, daftarkan member punguan, catat keuangan |
| `amang@` | Member Punguan Medan (menunggak 2 bulan), ayah dari `member@`; validator keluarga untuk pendaftaran `calon@` |
| `member@` | Member Punguan Medan (Sundut 12), iuran lancar |
| `calon@` | Calon member dengan pendaftaran keluarga yang menunggu validasi |

## Memperbarui kode di komputer lokal

```bash
git status                                        # pastikan tidak ada perubahan lokal (lihat catatan)
git fetch origin
git checkout claude/elegant-babbage-fnn1in
git pull origin claude/elegant-babbage-fnn1in
git log --oneline -1                              # harus sama dengan commit terbaru di GitHub
composer install                                  # bila composer.lock berubah
php spark migrate --all                           # tabel/kolom baru (mis. huta)
php spark db:seed DemoKeluargaSeeder              # opsional: contoh huta & keluarga beristri dua
```

- Footer setiap halaman menampilkan **Versi** aplikasi; bandingkan dengan `app/Config/Silsilah.php` (`$versi`).
- CSS/JS dimuat dengan penanda waktu (`app.css?v=…`), jadi browser otomatis mengambil versi terbaru.
- Bila `git pull` menolak karena ada perubahan lokal: `git stash` lalu ulangi `git pull`
  (atau `git checkout -- .` untuk membuang perubahan lokal).
- Bila kode diunduh sebagai ZIP, unduh ulang ZIP dari branch `claude/elegant-babbage-fnn1in`, bukan dari branch lain.

## Menjalankan

```bash
php spark serve     # buka http://localhost:8080
```

| Halaman | Akses |
|---|---|
| `/`, `/kenali-marga`, `/mapping` Mapping keturunan, `/silsilah`, `/generasi`, `/partuturan`, `/berita`, `/kegiatan`, `/punguan` | Publik |
| `/pendaftaran` Daftarkan keluarga, status, "Ini saya" | Login (calon member) |
| `/garis` Jalur saya, `/keluarga-dekat`, `/anggota/{id}`, `/hubungan`, `/konfirmasi-keluarga` | Member |
| `/admin/usulan`, `/admin/import` | Ketua Adat, Admin Marga, Penatua Punguan, Admin Wilayah (sesuai lingkup) |
| `/admin/leluhur-awal`, `/admin/partuturan`, `/admin/sejarah` | Ketua Adat |
| `/admin/berita`, `/admin/kegiatan` | Pengurus Informasi, Admin Marga, Ketua Adat, Penatua |
| `/admin/anggota` Data anggota (status hidup/meninggal) | Super Admin, Ketua Adat, Admin Marga, Penatua, Admin Wilayah |
| `/admin/punguan-anggota` Member punguan | Humas (mengajukan), Penatua (mengesahkan) |
| `/admin/keuangan` Keuangan punguan | Humas (mencatat), Penatua (memvalidasi) |
| `/admin/marga`, `/admin/punguan`, `/admin/pengguna` | Super Admin |

Alur pendaftaran: kepala keluarga mendaftarkan diri, istri, dan anak → **validator keluarga** (ayah/ompung atau anak/pahompu yang sudah member) membenarkan → **penatua punguan** mengesahkan. Lihat `docs/STANDAR.md` bagian 4.2.

### Aset frontend

Bootstrap, Bootstrap Icons, D3, dan font (Fraunces, Figtree) disimpan di `public/assets/vendor` (ikut di-commit), jadi server tidak butuh Node. Untuk memperbarui versinya:

```bash
npm install && npm run aset
```

## Import data

Lihat [`docs/PANDUAN-IMPORT.md`](docs/PANDUAN-IMPORT.md). Import bisa lewat menu Admin → Import Excel, atau lewat terminal untuk berkas besar:

```bash
php spark silsilah:import data.xlsx --marga PDS --simpan
```

## Test

Tes memakai database terpisah (`database.tests.*` di `.env`):

```bash
vendor/bin/phpunit
```

## Struktur penting

| Lokasi | Isi |
|---|---|
| `app/Services/PersonService.php` | Semua penambahan/perubahan data silsilah: aturan adat, closure table, hak akses, audit |
| `app/Services/SilsilahQuery.php` | Jalur leluhur, keturunan, pohon (lazy load), profil, rekap generasi |
| `app/Services/SilsilahPolicy.php` | Hak akses per role, marga, dan Silsilah Pokok |
| `app/Services/PartuturanService.php` | Mesin partuturan: panggilan antar dua anggota + jalur silsilah |
| `app/Services/UsulanService.php` | Pendaftaran, usulan, kesaksian keluarga, dan verifikasi |
| `app/Services/LingkupAdmin.php` | Lingkup Admin Wilayah (wilayah/cabang pomparan) |
| `app/Services/PunguanService.php` | Member Punguan: pengajuan, pengesahan Penatua, nonaktif |
| `app/Services/KeuanganService.php` | Keuangan punguan: kategori, catat, validasi, rekap iuran |
| `app/Services/PencarianService.php` | Saran pencarian: nama, kode, "anak ni …", nama + tempat, salah ketik |
| `public/assets/js/saran-orang.js` | Kartu saran pencarian (foto, huta, punguan) dengan papan ketik |
| `app/Services/ImportService.php` | Import Excel/CSV, semua-atau-tidak-sama-sekali |
| `public/assets/js/pohon.js` | Pohon interaktif D3 dengan lazy load per cabang |
| `app/Services/DataPribadiCipher.php` | Enkripsi NIK/No. KK (UU PDP) |
| `app/Config/Silsilah.php` | Daftar referensi (agama, pendidikan, dll.) |
| `app/Config/AuthGroups.php` | Role: superadmin, ketua_adat, verifikator, member |
| `app/Database/Migrations/` | Skema database |
| `docs/template-import-anggota.csv` | Template import data anggota |
