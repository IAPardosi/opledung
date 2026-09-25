# Panduan Import Data Anggota

Gunakan template [`template-import-anggota.csv`](template-import-anggota.csv). Bisa dibuka dan diisi di Excel, lalu disimpan sebagai `.xlsx` atau `.csv`.

## Kolom kunci

| Kolom | Arti |
|---|---|
| `kode_ref` | Kode sementara untuk baris ini, bebas tapi unik dalam satu file (mis. `A1`, `A2`). |
| `kode_induk` | Orang tua dalam garis marga: `kode_ref` baris **sebelumnya** atau kode anggota yang sudah ada di sistem (mis. `PDS-G10-000123`). Kosongkan hanya untuk leluhur awal (Generasi 1). |
| `jenis_kelamin` | `L`/`P` atau `Laki-laki`/`Perempuan`. |
| `nama_pasangan`, `marga_pasangan` | Bila diisi, pasangan (istri/suami) otomatis ditambahkan. |
| `kode_wilayah` | Kode Kemendagri, sampai desa bila diketahui (mis. `12.02.01.2001`). Minimal kab/kota (mis. `12.02`). |

## Aturan

- Urutkan baris dari generasi tertua ke termuda. Induk harus muncul sebelum anaknya.
- Generasi dihitung otomatis dari induknya. Garis (utama/boru/anak boru) juga ditentukan otomatis dari jenis kelamin dan induknya.
- Anak dari boru (anak boru) tidak dapat menjadi induk.
- Bila induk tepat memiliki satu pasangan, pasangan itu otomatis dicatat sebagai ibu/ayah anak.
- Tanggal: `YYYY-MM-DD` atau `DD/MM/YYYY`. Bila hanya tahun yang diketahui, isi `tahun_lahir`/`tahun_wafat`.
- Generasi Silsilah Pokok (1–10) hanya dapat diimport oleh Ketua Adat atau Super Admin.

## Proses

1. **Pratinjau**: semua baris diperiksa tanpa disimpan. Hasilnya berupa laporan per baris.
2. **Simpan**: dijalankan bila pratinjau tanpa kesalahan. Bila ada satu baris gagal, seluruh import dibatalkan sehingga data tidak setengah masuk.

Untuk file besar, import juga bisa lewat terminal:

```bash
php spark silsilah:import data.xlsx --marga PDS           # pratinjau
php spark silsilah:import data.xlsx --marga PDS --simpan  # simpan
```
