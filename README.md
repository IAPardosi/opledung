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

Seeder ini membuat sekitar 4.500 orang **fiktif** dalam 14 generasi, lengkap dengan boru, pasangan, dan anak boru. Juga dibuat akun demo `ketuaadat@`, `verifikator@`, dan `member@silsilah.local` dengan password `Demo#12345`. Ukurannya bisa diatur dengan `demo.generasi` dan `demo.maksPenerusPerGenerasi`.

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
| `app/Services/DataPribadiCipher.php` | Enkripsi NIK/No. KK (UU PDP) |
| `app/Config/Silsilah.php` | Daftar referensi (agama, pendidikan, dll.) |
| `app/Config/AuthGroups.php` | Role: superadmin, ketua_adat, verifikator, member |
| `app/Database/Migrations/` | Skema database |
| `docs/template-import-anggota.csv` | Template import data anggota |
