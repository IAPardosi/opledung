# Standar Pengembangan — Sistem Silsilah Marga (Opledung)

Dokumen ini adalah acuan resmi pengembangan fase pertama. Setiap perubahan
standar harus diperbarui di dokumen ini terlebih dahulu sebelum diterapkan di kode.

Versi: 3.0 · Status: Fase 1 + pengembangan (tema Adat Modern, validasi dua lapis, punguan, Kenali Marga, mode tampilan)

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
| Frontend        | Bootstrap 5 + tema "Adat Modern", JavaScript ringan (tanpa SPA framework); aset & font disimpan sendiri di `public/assets/vendor`, tanpa CDN |
| Visual pohon    | D3 v7 (tree layout), dimuat per cabang lewat `/api/pohon/{id}` |
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
| Super Admin            | Semua akses; membuat marga baru; mengatur role dan lingkup admin |
| Ketua Adat             | Menetapkan dan memvalidasi Silsilah Pokok (Generasi 1–10); menyesuaikan istilah partuturan |
| Admin Marga            | Memverifikasi pendaftaran dan usulan di seluruh marganya (Generasi 11 ke atas) |
| Penatua Punguan        | Ketua/penatua punguan daerah (dimulai dari Medan): mengesahkan pendaftaran dan usulan anggota punguannya (lapis 2) |
| Admin Wilayah          | Seperti penatua, tetapi lingkupnya wilayah domisili (provinsi/kab/kota) dan/atau cabang pomparan |
| Pengurus Informasi     | Mengelola berita dan kegiatan |
| Member                 | Anggota terverifikasi: melihat profil & partuturan, mengelola profil sendiri, mengusulkan data keluarga, memberi kesaksian keluarga |
| Calon Member           | Baru mendaftar; hanya dapat mengisi silsilah dan melihat status pendaftarannya |
| Publik (tanpa login)   | Pohon silsilah umum, daftar generasi, kamus partuturan, berita, dan kegiatan |

### 4.1 Alur Data
- Semua input dari Member masuk sebagai **usulan** dengan status
  `pending → disetujui / ditolak` sebelum masuk ke silsilah resmi.
- Data Silsilah Pokok hanya bisa berubah melalui Ketua Adat.
- Setiap perubahan dicatat di `audit_logs` (siapa, kapan, data lama, data baru).

### 4.2 Pendaftaran Kepala Keluarga dan Validasi Dua Lapis
1. **Buat akun** → otomatis berstatus *Calon Member*.
2. **Kepala keluarga mendaftarkan unit keluarganya**: pilih punguan, pilih *leluhur terdekat
   yang sudah tercatat* (mulai Sundut 10), isi generasi antara sampai ayah, data diri,
   lalu **istri/suami dan anak-anak**. Sundut dihitung otomatis.
   Anak yang kelak mendaftar (mis. setelah menikah) **tidak mengisi ulang silsilah**, cukup
   menekan **"Ini saya"** pada data yang sudah dicatat orang tuanya, sehingga silsilah satu
   keluarga selalu sama.
3. **Lapis 1, validasi keluarga**: pengusul menunjuk satu member sah dalam **garis langsung,
   paling jauh 2 sundut ke atas atau ke bawah** (ayah/ibu, ompung, anak, atau pahompu).
   Validator menyatakan *benar* atau *tidak benar* (wajib beralasan).
   - Member yang mengusulkan keluarganya sendiri (mis. ayah menambah anaknya) otomatis
     dianggap sah keluarga.
   - Bila tidak ada member dalam garis langsung, usulan ditandai *tanpa validator* dan
     penatua memeriksa lebih teliti.
   - Bila validator tidak dapat dihubungi, penatua boleh **melewati** lapis 1 dengan alasan
     tertulis (tercatat).
4. **Lapis 2, pengesahan punguan**: Penatua Punguan (sesuai punguan pengusul), Admin Marga,
   atau Ketua Adat untuk Silsilah Pokok mengesahkan atau menolak. Pengesahan **tidak bisa**
   dilakukan selama lapis 1 masih menunggu atau menyatakan tidak benar.
5. Setelah disahkan: generasi antara, kepala keluarga, pasangan, dan anak-anak dicatat;
   akun ditautkan ke datanya, masuk punguan, dan naik menjadi Member.
6. Kesaksian kerabat lain (satu pomparan) tetap dapat diberikan sebagai pelengkap.

### 4.3 Punguan (Pusat, Daerah, Global)
- Silsilah **tetap satu pohon** di satu database pusat; punguan hanya lapisan organisasi.
- Tingkat: Pusat → Daerah (dimulai **Punguan Medan**) → Global (luar negeri, dengan negara).
- Setiap member memilih punguan; pendaftaran dan usulan diteruskan ke penatua punguan itu.
- Kegiatan dan berita dapat dikhususkan untuk satu punguan.
- Punguan baru ditambahkan Super Admin di Admin → Punguan.

## 4A. Partuturan
- Sistem menghitung cara memanggil antara dua anggota dari pohon: cari titik temu
  (leluhur bersama terdekat), jarak sundut, jenis kelamin, dan apakah salah satunya
  turun lewat boru.
- **Haha–anggi dan amangtua–amanguda ditentukan dari garis yang lebih sulung** di titik
  temu (urutan anak), bukan dari umur.
- Pasangan (istri/suami dari marga lain) dipanggil sesuai panggilan kepada pasangannya
  (mis. istri tulang → nantulang, suami namboru → amangboru, istri anak → parumaen).
- Istilah disimpan di tabel `partuturan` dan **dapat disesuaikan Ketua Adat**; aturan
  penentuannya ada di `app/Services/PartuturanService.php` dan diuji di
  `tests/database/PartuturanTest.php`.

## 4B. Kenali Marga dan Mode Tampilan
- **Kenali Marga** (publik): kisah marga, bona pasogit, **leluhur sebelum marga** (4–5
  generasi, informasi sejarah, *tidak dihitung sundut*), urutan besar sampai sundut aktif,
  dan kartu sundut 1–3. Diisi Ketua Adat di Admin → Sejarah marga.
- **Empat mode tampilan silsilah** (bisa berpindah dengan satu klik):
  | Mode | Isi |
  |---|---|
  | Jalur saya | Garis lurus Sundut 1 → saya (ringkas: sundut tengah dilipat; lengkap: semua), dengan jumlah saudara di tiap sundut |
  | Keluarga dekat | Saya di tengah: ompung, orang tua & saudaranya, saudara, anak, pahompu, beserta partuturan |
  | Per sundut | Daftar satu generasi dengan filter dan pencarian |
  | Pohon cabang | Pohon interaktif dibuka bertahap per cabang |

## 4C. Tampilan (Tema "Adat Modern")
- Palet gorga: merah `#a3161e`, hitam `#16110f`, putih; latar hangat `#f6f3f0`.
- Tipografi: Bricolage Grotesque (judul) dan Figtree (isi), disimpan di server sendiri.
- Navigasi kapsul melayang di atas; **navigasi bawah** di HP (Beranda, Silsilah, Tutur, Kabar, Akun).
- Kartu bento, sudut membulat, ornamen gorga hanya sebagai aksen tipis.

## 4D. Kanal Informasi
- **Berita**: kategori berita, pengumuman, sukacita, dukacita; status draf/terbit.
- **Kegiatan**: pesta adat, bona taon, partangiangan, arisan/punguan, rapat, sosial, dll.
  dengan waktu, tempat, kab/kota, tautan peta, dan narahubung.
- Isi ditulis sebagai teks sederhana (paragraf, `**tebal**`, `*miring*`, `- daftar`,
  `[teks](https://…)`); HTML dari pengguna tidak pernah dijalankan.
- Gambar diubah ulang ke JPEG oleh server sebelum disimpan.

## 5. Hak Lihat (Privasi)

| Informasi                                   | Publik | Member login | Admin/Ketua Adat |
|---------------------------------------------|:------:|:------------:|:----------------:|
| Pohon silsilah umum (nama, generasi, garis), berita, kegiatan, kamus partuturan |   ✔    |      ✔       |        ✔         |
| Halaman profil per anggota & partuturan     |   ✘    |  ✔ (member)  |        ✔         |
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
| `partuturan`       | Istilah tutur sapa yang dapat disesuaikan Ketua Adat |
| `admin_lingkup`    | Lingkup Admin Wilayah (kode wilayah atau cabang leluhur) |
| `konfirmasi_keluarga` | Kesaksian kerabat atas pendaftaran/usulan |
| `berita`, `kegiatan` | Kanal informasi (bisa per punguan) |
| `punguan`          | Organisasi pomparan per daerah (Pusat/Daerah/Global) |
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
5. Import massal Excel/CSV dengan template standar (`docs/template-import-anggota.csv`, panduan di `docs/PANDUAN-IMPORT.md`)
   dan validasi per baris (laporan baris yang gagal).
6. Halaman profil anggota: data diri, orang tua, pasangan, anak, saudara kandung.
7. Jalur ke leluhur: `G1 → G2 → … → anggota`.
8. Pohon silsilah interaktif (publik: umum; login: detail per anggota).
9. Daftar anggota per generasi dengan filter (generasi, garis, wilayah, status hidup) dan pencarian.

**Sudah ditambahkan (v3.0):** tema Adat Modern, pendaftaran kepala keluarga + validasi dua lapis
(validator keluarga garis langsung + penatua punguan), punguan (mulai Medan), Kenali Marga,
empat mode tampilan silsilah.

**Sudah ditambahkan (v2.0):** partuturan (cek hubungan + kamus), pendaftaran member
berlapis (kesaksian keluarga + Admin Wilayah), berita & kegiatan, tema visual gorga.

**Fase berikutnya:** peta sebaran, cetak tarombo PDF, notifikasi (email/WhatsApp),
API mobile.

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
