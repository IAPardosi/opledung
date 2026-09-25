<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Services\KontenService;
use CodeIgniter\Database\Seeder;

/**
 * Berita dan kegiatan CONTOH (fiktif) untuk development.
 */
class DemoKontenSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production' || $this->db->table('berita')->countAllResults() > 0) {
            return;
        }

        $margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $konten  = new KontenService();

        $berita = [
            ['pengumuman', 'Pendaftaran Member Tarombo Dibuka (contoh)', 'Seluruh pomparan diajak mendaftar dan melengkapi silsilah keluarganya.',
                "Horas! Situs tarombo kini dapat dipakai seluruh pomparan.\n\nLangkahnya:\n\n- Buat akun di menu **Daftar Member**.\n- Pilih leluhur terdekat yang sudah tercatat, lalu lengkapi generasi di antaranya.\n- Minta kerabat dekat memberi kesaksian, lalu Admin Wilayah akan memvalidasi.\n\nGenerasi 1–10 sudah diisi dan divalidasi Ketua Adat.", 1],
            ['berita', 'Rapat Pengurus Membahas Pesta Bona Taon (contoh)', 'Pengurus punguan menyepakati waktu dan tempat pesta bona taon tahun depan.',
                "Rapat pengurus berlangsung di Medan dan dihadiri perwakilan tiap wilayah.\n\nKeputusan penting: pesta bona taon akan dilaksanakan pada awal tahun, dengan panitia dari wilayah Jabodetabek.", 4],
            ['sukacita', 'Selamat atas Pernikahan Putra-Putri Pomparan (contoh)', 'Ucapan selamat kepada keluarga yang berbahagia.',
                "Dengan sukacita kami sampaikan selamat kepada keluarga yang telah melangsungkan pernikahan secara adat.\n\nSemoga menjadi keluarga yang **saur matua**, gabe, dan horas.", 9],
            ['dukacita', 'Turut Berdukacita (contoh)', 'Keluarga besar turut berdukacita atas berpulangnya salah satu ompung kita.',
                "Keluarga besar pomparan turut berdukacita. Kiranya keluarga yang ditinggalkan diberi penghiburan.\n\nIbadah penghiburan diumumkan melalui menu Kegiatan.", 15],
            ['berita', 'Beasiswa Pendidikan untuk Pomparan (contoh)', 'Punguan membuka bantuan pendidikan bagi anak-anak pomparan yang berprestasi.',
                "Punguan membuka bantuan pendidikan untuk pelajar SMA dan mahasiswa.\n\nSyarat dan formulir dapat diminta kepada pengurus wilayah masing-masing.", 22],
        ];
        foreach ($berita as [$kategori, $judul, $ringkasan, $isi, $hariLalu]) {
            $this->db->table('berita')->insert([
                'marga_id'   => $margaId,
                'kategori'   => $kategori,
                'judul'      => $judul,
                'slug'       => $konten->slugUnik('berita', $judul),
                'ringkasan'  => $ringkasan,
                'isi'        => $isi,
                'status'     => 'terbit',
                'terbit_at'  => date('Y-m-d H:i:s', strtotime("-{$hariLalu} days")),
                'dilihat'    => random_int(10, 400),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $kegiatan = [
            ['bona_taon', 'Pesta Bona Taon Pomparan (contoh)', '+45 days 10:00', 'Gedung Serbaguna (contoh)', '31.71', "Susunan acara:\n\n- Ibadah bersama\n- Kata sambutan pengurus\n- Makan bersama dan manortor\n\nPeserta diharapkan memakai **ulos**."],
            ['partangiangan', 'Partangiangan Wilayah Medan (contoh)', '+10 days 19:00', 'Rumah keluarga (contoh)', '12.71', 'Partangiangan rutin bulanan wilayah Medan.'],
            ['arisan', 'Arisan Punguan Wilayah Jabodetabek (contoh)', '+20 days 14:00', 'Sopo Punguan (contoh)', '31.71', 'Arisan bulanan sekaligus pendataan anggota baru.'],
            ['rapat', 'Rapat Pengurus Pusat (contoh)', '-12 days 13:00', 'Balige (contoh)', '12.12', 'Evaluasi program kerja semester pertama.'],
        ];
        foreach ($kegiatan as [$jenis, $judul, $waktu, $lokasi, $kab, $deskripsi]) {
            $this->db->table('kegiatan')->insert([
                'marga_id'       => $margaId,
                'jenis'          => $jenis,
                'judul'          => $judul,
                'slug'           => $konten->slugUnik('kegiatan', $judul),
                'deskripsi'      => $deskripsi,
                'mulai'          => date('Y-m-d H:i:s', strtotime($waktu)),
                'lokasi'         => $lokasi,
                'kabupaten_kode' => $this->db->table('wilayah')->where('kode', $kab)->countAllResults() ? $kab : null,
                'kontak'         => 'Sekretaris punguan · 0812-0000-0000 (contoh)',
                'status'         => 'terbit',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
