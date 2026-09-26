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

Seeder ini membuat sekitar 4.500 orang **fiktif** dalam 14 generasi, lengkap dengan boru, pasangan, dan anak boru, serta contoh berita, kegiatan, dan satu pendaftaran yang menunggu validasi.

Akun demo (password `Demo#12345`), semuanya `@silsilah.local`:

| Akun | Role |
|---|---|
| `ketuaadat@` | Ketua Adat (Silsilah Pokok, partuturan, sejarah marga) |
| `verifikator@` | Admin Marga |
| `penatua@` | Penatua Punguan Medan (lapis 2) |
| `humas@` | Pengurus Informasi (berita & kegiatan) |
| `amang@` | Member, ayah dari `member@`; validator keluarga untuk pendaftaran `calon@` |
| `member@` | Member (Sundut 12) |
| `calon@` | Calon member dengan pendaftaran keluarga yang menunggu validasi |

## Menjalankan

```bash
php spark serve     # buka http://localhost:8080
```

| Halaman | Akses |
|---|---|
| `/`, `/kenali-marga`, `/silsilah`, `/generasi`, `/partuturan`, `/berita`, `/kegiatan`, `/punguan` | Publik |
| `/pendaftaran` Daftarkan keluarga, status, "Ini saya" | Login (calon member) |
| `/garis` Jalur saya, `/keluarga-dekat`, `/anggota/{id}`, `/hubungan`, `/konfirmasi-keluarga` | Member |
| `/admin/usulan`, `/admin/import` | Ketua Adat, Admin Marga, Penatua Punguan, Admin Wilayah (sesuai lingkup) |
| `/admin/leluhur-awal`, `/admin/partuturan`, `/admin/sejarah` | Ketua Adat |
| `/admin/berita`, `/admin/kegiatan` | Pengurus Informasi, Admin Marga, Ketua Adat, Penatua |
| `/admin/marga`, `/admin/punguan`, `/admin/pengguna` | Super Admin |

Alur pendaftaran: kepala keluarga mendaftarkan diri, istri, dan anak → **validator keluarga** (ayah/ompung atau anak/pahompu yang sudah member) membenarkan → **penatua punguan** mengesahkan. Lihat `docs/STANDAR.md` bagian 4.2.

### Aset frontend

Bootstrap, Bootstrap Icons, D3, dan font (Bricolage Grotesque, Figtree) disimpan di `public/assets/vendor` (ikut di-commit), jadi server tidak butuh Node. Untuk memperbarui versinya:

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
| `app/Services/ImportService.php` | Import Excel/CSV, semua-atau-tidak-sama-sekali |
| `public/assets/js/pohon.js` | Pohon interaktif D3 dengan lazy load per cabang |
| `app/Services/DataPribadiCipher.php` | Enkripsi NIK/No. KK (UU PDP) |
| `app/Config/Silsilah.php` | Daftar referensi (agama, pendidikan, dll.) |
| `app/Config/AuthGroups.php` | Role: superadmin, ketua_adat, verifikator, member |
| `app/Database/Migrations/` | Skema database |
| `docs/template-import-anggota.csv` | Template import data anggota |
