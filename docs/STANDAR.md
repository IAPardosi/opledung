# Standar Pengembangan — Sistem Silsilah Marga (Opledung)

Dokumen ini adalah acuan resmi pengembangan fase pertama. Setiap perubahan
standar harus diperbarui di dokumen ini terlebih dahulu sebelum diterapkan di kode.

Versi: 1.1 · Status: Disepakati untuk Fase 1

---

## 1. Ruang Lingkup

- Marga awal: **Pardosi** (rumpun Op. Ledung) dengan seluruh keturunannya.
- Sistem bersifat **multi-marga**: Super Admin dapat menambahkan marga lain di
  kemudian hari tanpa mengubah kode. Semua data selalu terikat ke `marga_id`.
- Bahasa antarmuka: **Bahasa Indonesia** saja.
- Fokus Fase 1: input data anggota, profil anggota, dan tampilan silsilah.

## 2. Teknologi

| Komponen        | Standar                                               |
|-----------------|-------------------------------------------------------|
| Framework       | CodeIgniter 4 (versi stabil terbaru)                  |
| PHP             | 8.2 atau lebih baru                                   |
| Database        | MySQL 8 / MariaDB 10.6+ (wajib dukung recursive CTE)  |
| Autentikasi     | CodeIgniter Shield                                    |
| Frontend        | Bootstrap 5, JavaScript ringan (tanpa SPA framework)  |
| Visual pohon    | Library pohon keluarga berbasis D3 (mis. family-chart), dimuat per cabang |
| Import data     | PhpSpreadsheet (Excel .xlsx dan CSV)                  |

## 3. Aturan Adat yang Diterapkan di Sistem

### 3.1 Penomoran Generasi (Sundut)
- Nomor generasi mengikuti **penomoran adat**.
- `generasi_ke` anak = `generasi_ke` ayah + 1 (dihitung otomatis, tidak diketik manual),
  kecuali untuk Generasi 1 yang ditetapkan oleh Ketua Adat.
- **Generasi 1 s.d. 10 adalah Silsilah Pokok**: hanya dapat diinput, diubah, dan
  divalidasi oleh **Ketua Adat**. Data berstatus terkunci setelah divalidasi.
- Generasi aktif sebagai member saat ini: **10 s.d. 14**. Sistem tidak membatasi
  jumlah generasi (siap untuk 18+ generasi).
- Batas Silsilah Pokok (angka 10) disimpan sebagai pengaturan per marga,
  bukan ditulis mati di kode.

### 3.2 Garis Keturunan (Patrilineal)
- Garis keturunan diteruskan melalui **anak laki-laki**.
- **Boru (anak perempuan)** tetap dicatat sebagai anak. Untuk boru dicatat:
  - suaminya (nama dan marga suami), dan
  - anak-anaknya (nama saja/data singkat).
- **Anak dari boru tidak diproses lebih lanjut**: tidak bisa ditambahkan anak/cucu
  di bawahnya dan tidak masuk hitungan generasi marga. Di sistem ditandai
  `garis = 'anak_boru'` sebagai ujung cabang.
- Istri dari anggota laki-laki (dari marga lain) dicatat sebagai pasangan,
  bukan sebagai anggota garis marga.

### 3.3 Pernikahan
- Satu orang dapat memiliki lebih dari satu pernikahan (tercatat berurutan).
- Anak selalu dihubungkan ke ayah dan (bila diketahui) ke pernikahan/ibunya.

## 4. Peran Pengguna (Role)

| Role                   | Hak Akses |
|------------------------|-----------|
| Super Admin            | Semua akses; membuat marga baru; mengelola admin dan Ketua Adat |
| Ketua Adat             | Menetapkan dan memvalidasi Silsilah Pokok (Generasi 1–10) marganya |
| Admin/Verifikator      | Memverifikasi usulan data anggota (Generasi 11 ke atas) di marganya |
| Member                 | Mengelola profil sendiri; mengusulkan tambah/ubah data keluarga |
| Publik (tanpa login)   | Melihat pohon silsilah umum saja |

### 4.1 Alur Data
- Semua input dari Member masuk sebagai **usulan** dengan status
  `pending → disetujui / ditolak` sebelum masuk ke silsilah resmi.
- Data Silsilah Pokok hanya bisa berubah melalui Ketua Adat.
- Setiap perubahan dicatat di `audit_logs` (siapa, kapan, data lama, data baru).

## 5. Hak Lihat (Privasi)

| Informasi                                   | Publik | Member login | Admin/Ketua Adat |
|---------------------------------------------|:------:|:------------:|:----------------:|
| Pohon silsilah umum (nama, generasi, garis) |   ✔    |      ✔       |        ✔         |
| Halaman silsilah/profil per anggota         |   ✘    |      ✔       |        ✔         |
| Tanggal lahir, alamat, kontak (masih hidup) |   ✘    |      ✔*      |        ✔         |
| NIK / No. KK                                |   ✘    |      ✘       |   ✔ (terenkripsi)|

\* Anggota dapat mengatur agar kontak/alamatnya disembunyikan.

Pengelolaan data pribadi mengikuti prinsip **UU No. 27 Tahun 2022 tentang
Pelindungan Data Pribadi**: NIK dan No. KK bersifat opsional, disimpan terenkripsi,
dan tidak pernah ditampilkan ke publik atau member lain.

## 6. Struktur Data

### 6.1 Tabel Inti

| Tabel              | Fungsi |
|--------------------|--------|
| `marga`            | Data marga: nama, leluhur awal, asal/bona pasogit, batas Silsilah Pokok |
| `persons`          | Setiap orang (anggota garis marga, boru, pasangan, anak boru) |
| `marriages`        | Pernikahan: suami, istri, urutan, tanggal, status |
| `person_paths`     | Closure table: semua pasangan leluhur→keturunan beserta jarak |
| `users`            | Akun login (Shield), dapat ditautkan ke satu `persons` |
| `change_requests`  | Usulan tambah/ubah data dari member (status pending/disetujui/ditolak) |
| `audit_logs`       | Riwayat semua perubahan |
| `wilayah`          | Referensi wilayah Indonesia (provinsi, kab/kota, kecamatan, desa) kode Kemendagri |

### 6.2 Strategi Pohon untuk Skala Besar
- **Adjacency list** (`ayah_id`, `ibu_id` di `persons`) sebagai sumber data utama.
- **Closure table** (`person_paths`) diperbarui otomatis setiap ada anggota baru, sehingga:
  - "semua keturunan si A" dan "jalur si B ke Generasi 1" cukup satu query berindeks;
  - tidak perlu query berulang per generasi.
- Tampilan pohon dimuat **bertahap per cabang** (misalnya 3 generasi sekali muat),
  bukan seluruh pohon sekaligus.

### 6.3 Identitas Anggota
- Setiap orang memiliki **kode unik** otomatis: `{KODE_MARGA}-G{generasi 2 digit}-{nomor urut 6 digit}`,
  contoh `PDS-G12-000345`.
- Identitas selalu memakai kode/ID, **tidak pernah memakai nama** (nama sama sangat mungkin).
- Pasangan (istri/suami dari marga lain) memakai nomor generasi pasangannya di kodenya.
- Nomor urut tidak pernah dipakai ulang, termasuk setelah data dihapus.

## 7. Standar Profil Anggota (Data Warga Indonesia)

Field mengikuti data kependudukan Indonesia (KTP/KK) ditambah data adat.

### 7.1 Data Adat / Silsilah
| Field              | Keterangan | Wajib |
|--------------------|------------|:-----:|
| kode_anggota       | Otomatis   | ✔ |
| marga              | Pilihan    | ✔ |
| generasi_ke        | Otomatis dari ayah (G1 oleh Ketua Adat) | ✔ |
| garis              | `utama` / `boru` / `anak_boru` / `pasangan` (otomatis) | ✔ |
| kode_ayah          | Kode anggota ayah | ✔ (kecuali G1) |
| nama_ibu / kode_ibu| Nama ibu atau tautan ke data ibu | – |
| urutan_anak        | Anak ke-berapa | – |
| gelar_adat / nama_sapaan | Mis. "Op. …", "Ama ni …" | – |

### 7.2 Data Pribadi
| Field                | Format / Pilihan | Wajib |
|----------------------|------------------|:-----:|
| nama_lengkap         | Sesuai KTP | ✔ |
| nama_panggilan       | Teks | – |
| jenis_kelamin        | Laki-laki / Perempuan | ✔ |
| tempat_lahir         | Kab/Kota | – |
| tanggal_lahir        | `YYYY-MM-DD` (boleh hanya tahun untuk leluhur) | – |
| agama                | Islam, Kristen Protestan, Katolik, Hindu, Buddha, Konghucu, Kepercayaan | – |
| status_perkawinan    | Belum Kawin, Kawin, Cerai Hidup, Cerai Mati | – |
| pendidikan_terakhir  | Tidak/Belum Sekolah, SD, SMP, SMA/SMK, D1–D3, D4/S1, S2, S3 | – |
| pekerjaan            | Teks (acuan daftar pekerjaan Dukcapil) | – |
| golongan_darah       | A, B, AB, O, Tidak Tahu | – |
| kewarganegaraan      | WNI / WNA | – |
| nik                  | 16 digit, terenkripsi | – |
| no_kk                | 16 digit, terenkripsi | – |

### 7.3 Alamat & Kontak
| Field                    | Keterangan |
|--------------------------|------------|
| alamat_jalan             | Jalan, nomor rumah |
| rt / rw                  | 3 digit |
| desa_kelurahan           | Kode wilayah Kemendagri |
| kecamatan                | Kode wilayah Kemendagri |
| kabupaten_kota           | Kode wilayah Kemendagri |
| provinsi                 | Kode wilayah Kemendagri |
| kode_pos                 | 5 digit |
| no_hp                    | Format `08…` / `+62…` |
| email                    | Opsional |

### 7.4 Status Hidup
| Field            | Keterangan |
|------------------|------------|
| status_hidup     | Hidup / Meninggal |
| tanggal_wafat    | `YYYY-MM-DD` atau tahun saja |
| tempat_makam     | Teks (mis. tugu/makam keluarga) |

### 7.5 Profil Tambahan
- Foto (JPG/PNG/WebP, maks. 2 MB, otomatis dikompres).
- Biografi/riwayat singkat (teks).
- Untuk leluhur (Silsilah Pokok): kisah/sejarah, asal kampung, dan dokumen pendukung.

## 8. Fitur Fase 1 (MVP)

1. Login, registrasi, dan role (Shield).
2. Kelola marga (Super Admin) dan Silsilah Pokok G1–G10 (Ketua Adat).
3. Tambah/ubah/cari anggota: tambah anak, tambah pasangan, tambah boru beserta suami dan anaknya.
4. Alur usulan dan verifikasi data.
5. Import massal Excel/CSV dengan template standar (`docs/template-import-anggota.csv`)
   dan validasi per baris (laporan baris yang gagal).
6. Halaman profil anggota: data diri, orang tua, pasangan, anak, saudara kandung.
7. Jalur ke leluhur: `G1 → G2 → … → anggota`.
8. Pohon silsilah interaktif (publik: umum; login: detail per anggota).
9. Daftar anggota per generasi dengan filter (generasi, garis, wilayah, status hidup) dan pencarian.

**Fase berikutnya:** cek hubungan antar dua anggota, panggilan partuturan,
peta sebaran, cetak tarombo PDF, notifikasi, API mobile.

## 9. Standar Kode

- Arsitektur CI4: **Controller → Service → Model**; tidak ada query di View maupun Controller.
- Semua perubahan skema melalui **Migration**; data contoh/referensi melalui **Seeder**.
- Gaya kode **PSR-12**; nama tabel/kolom `snake_case`; nama class `PascalCase`.
- Label antarmuka dan pesan validasi dalam Bahasa Indonesia.
- Validasi di server untuk semua input; CSRF aktif; output di-escape (`esc()`).
- Soft delete untuk `persons`, `marriages`, `users`; data tidak pernah dihapus permanen oleh pengguna biasa.
- Semua daftar memakai paginasi; kolom pencarian/relasi wajib berindeks.
- Unggahan file dibatasi tipe dan ukuran, disimpan di luar folder `public` dengan nama acak.
- Konfigurasi rahasia hanya di `.env` (tidak di-commit).

## 10. Standar Git

- Branch per fitur: `fitur/<nama>`, perbaikan: `perbaikan/<nama>`.
- Pesan commit singkat dan jelas dalam Bahasa Indonesia atau Inggris, konsisten per proyek.
- Setiap migration baru disertai rollback (`down()`) yang benar.
