<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Istilah partuturan bawaan (Batak Toba). Ketua Adat dapat menyesuaikan
 * sebutan/keterangan lewat menu Admin → Partuturan. Seeder ini tidak menimpa
 * istilah yang sudah ada.
 */
class PartuturanSeeder extends Seeder
{
    /**
     * kunci => [sebutan, keterangan, kelompok]
     */
    public const ISTILAH = [
        'diri'          => ['Diri sendiri', 'Orang yang sama.', 'lainnya'],

        // Garis ke atas
        'ayah'          => ['Amang (Bapa)', 'Ayah kandung.', 'orang_tua'],
        'ibu'           => ['Inang (Oma)', 'Ibu kandung, atau istri ayah.', 'orang_tua'],
        'ompung_doli'   => ['Ompung Doli', 'Kakek: ayah dari ayah/ibu, atau dongan tubu dua sundut di atas.', 'ompung'],
        'ompung_boru'   => ['Ompung Boru', 'Nenek: istri ompung doli, atau boru dua sundut di atas.', 'ompung'],
        'ompu'          => ['Ompu', 'Leluhur tiga sundut atau lebih di atas.', 'ompung'],

        // Garis ke bawah
        'anak'          => ['Anak', 'Anak laki-laki, termasuk anak dari haha-anggi (dongan tubu).', 'keturunan'],
        'boru'          => ['Boru', 'Anak perempuan, termasuk boru dari haha-anggi (dongan tubu).', 'keturunan'],
        'pahompu'       => ['Pahompu', 'Cucu.', 'keturunan'],
        'nini'          => ['Nini', 'Cicit laki-laki.', 'keturunan'],
        'nono'          => ['Nono', 'Cicit perempuan.', 'keturunan'],
        'ondok_ondok'   => ['Ondok-ondok', 'Cucu dari cucu.', 'keturunan'],
        'pomparan'      => ['Pomparan', 'Keturunan lima sundut atau lebih di bawah.', 'keturunan'],

        // Sesundut (satu generasi)
        'haha'          => ['Haha (Angkang)', 'Saudara sejenis kelamin dari garis yang lebih sulung.', 'sesundut'],
        'anggi'         => ['Anggi', 'Saudara sejenis kelamin dari garis yang lebih bungsu.', 'sesundut'],
        'ito'           => ['Ito', 'Saudara berlainan jenis kelamin, semarga, atau anak namboru bagi laki-laki / anak tulang bagi perempuan.', 'sesundut'],
        'lae'           => ['Lae', 'Sesama laki-laki: anak namboru, anak tulang, atau suami ito.', 'sesundut'],
        'pariban'       => ['Pariban', 'Bagi laki-laki: boru ni tulang. Bagi perempuan: anak laki-laki namboru.', 'sesundut'],
        'eda'           => ['Eda', 'Sesama perempuan: boru ni tulang, boru ni namboru, atau istri ito.', 'sesundut'],

        // Satu sundut di atas
        'amangtua'      => ['Amangtua (Bapatua)', 'Abang ayah atau dongan tubu ayah dari garis lebih sulung; juga suami kakak ibu.', 'paman_bibi'],
        'amanguda'      => ['Amanguda (Bapauda)', 'Adik ayah atau dongan tubu ayah dari garis lebih bungsu; juga suami adik ibu.', 'paman_bibi'],
        'inangtua'      => ['Inangtua (Maktua)', 'Istri amangtua, atau kakak perempuan ibu.', 'paman_bibi'],
        'inanguda'      => ['Inanguda (Nanguda)', 'Istri amanguda, atau adik perempuan ibu.', 'paman_bibi'],
        'namboru'       => ['Namboru (Bou)', 'Saudara perempuan ayah, atau boru semarga setingkat ayah.', 'paman_bibi'],
        'amangboru'     => ['Amangboru', 'Suami namboru.', 'paman_bibi'],
        'tulang'        => ['Tulang', 'Saudara laki-laki ibu, atau semarga ibu setingkat ibu.', 'paman_bibi'],
        'nantulang'     => ['Nantulang', 'Istri tulang.', 'paman_bibi'],

        // Satu sundut di bawah
        'bere'          => ['Bere', 'Anak dari saudara perempuan (ito) bagi laki-laki.', 'keponakan'],
        'anak_ni_ito'   => ['Amang (anak ni ito)', 'Panggilan perempuan kepada anak laki-laki saudara laki-lakinya.', 'keponakan'],
        'parumaen'      => ['Parumaen', 'Menantu perempuan; juga boru ni ito bagi seorang perempuan.', 'keponakan'],
        'hela'          => ['Hela', 'Menantu laki-laki: suami dari boru.', 'keponakan'],
        'ompung_naposo' => ['Ompung Naposo', 'Panggilan laki-laki kepada cucu laki-laki tulang.', 'keponakan'],

        // Perkawinan
        'istri'         => ['Istri (Parsonduk Bolon)', 'Istri.', 'perkawinan'],
        'suami'         => ['Suami', 'Suami.', 'perkawinan'],
        'angkang_boru'  => ['Angkang Boru', 'Istri dari haha.', 'perkawinan'],
        'anggi_boru'    => ['Anggi Boru', 'Istri dari anggi.', 'perkawinan'],
        'inang_bao'     => ['Inangbao', 'Istri dari hula-hula atau tunggane (mis. istri anak tulang).', 'perkawinan'],
        'simatua_doli'  => ['Simatua Doli', 'Mertua laki-laki.', 'perkawinan'],
        'simatua_boru'  => ['Simatua Boru', 'Mertua perempuan.', 'perkawinan'],

        // Cadangan bila tidak ada istilah khusus
        'kerabat'       => ['Kerabat', 'Masih satu silsilah; istilah khusus belum ditetapkan, tanyakan kepada Ketua Adat.', 'lainnya'],
    ];

    public function run(): void
    {
        $urutan = 0;
        foreach (self::ISTILAH as $kunci => [$sebutan, $keterangan, $kelompok]) {
            $urutan += 10;
            if ($this->db->table('partuturan')->where('kunci', $kunci)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('partuturan')->insert([
                'kunci'      => $kunci,
                'sebutan'    => $sebutan,
                'keterangan' => $keterangan,
                'kelompok'   => $kelompok,
                'urutan'     => $urutan,
            ]);
        }
    }
}
