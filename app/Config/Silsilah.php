<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan dan daftar referensi silsilah (lihat docs/STANDAR.md bagian 7).
 */
class Silsilah extends BaseConfig
{
    /**
     * Jumlah generasi yang dimuat sekali buka pada tampilan pohon.
     */
    public int $kedalamanMuatPohon = 3;

    /**
     * Batas Silsilah Pokok bawaan untuk marga baru.
     */
    public int $batasSilsilahPokokBawaan = 10;

    /**
     * @var array<string, string>
     */
    public array $jenisKelamin = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    /**
     * @var array<string, string>
     */
    public array $garis = [
        'utama'     => 'Garis Utama',
        'boru'      => 'Boru',
        'anak_boru' => 'Anak Boru',
        'pasangan'  => 'Pasangan',
    ];

    /**
     * @var list<string>
     */
    public array $agama = [
        'Islam',
        'Kristen Protestan',
        'Katolik',
        'Hindu',
        'Buddha',
        'Konghucu',
        'Kepercayaan',
    ];

    /**
     * @var list<string>
     */
    public array $statusPerkawinan = [
        'Belum Kawin',
        'Kawin',
        'Cerai Hidup',
        'Cerai Mati',
    ];

    /**
     * @var list<string>
     */
    public array $pendidikan = [
        'Tidak/Belum Sekolah',
        'SD',
        'SMP',
        'SMA/SMK',
        'D1',
        'D2',
        'D3',
        'D4/S1',
        'S2',
        'S3',
    ];

    /**
     * @var list<string>
     */
    public array $golonganDarah = ['A', 'B', 'AB', 'O', 'Tidak Tahu'];

    /**
     * @var list<string>
     */
    public array $kewarganegaraan = ['WNI', 'WNA'];

    /**
     * @var array<string, string>
     */
    public array $statusHidup = [
        'hidup'           => 'Hidup',
        'meninggal'       => 'Meninggal',
        'tidak_diketahui' => 'Tidak Diketahui',
    ];
}
