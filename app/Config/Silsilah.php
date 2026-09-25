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

    /**
     * Label kolom data orang untuk tampilan (mis. detail usulan).
     *
     * @var array<string, string>
     */
    public array $labelKolom = [
        'nama_lengkap'        => 'Nama lengkap',
        'nama_panggilan'      => 'Nama panggilan',
        'gelar_adat'          => 'Gelar adat',
        'jenis_kelamin'       => 'Jenis kelamin',
        'urutan_anak'         => 'Anak ke-',
        'pasangan_id'         => 'Ibu/ayah',
        'nama_ibu'            => 'Nama ibu',
        'marga_nama'          => 'Marga',
        'tempat_lahir'        => 'Tempat lahir',
        'tanggal_lahir'       => 'Tanggal lahir',
        'tahun_lahir'         => 'Tahun lahir',
        'agama'               => 'Agama',
        'status_perkawinan'   => 'Status perkawinan',
        'pendidikan_terakhir' => 'Pendidikan terakhir',
        'pekerjaan'           => 'Pekerjaan',
        'golongan_darah'      => 'Golongan darah',
        'kewarganegaraan'     => 'Kewarganegaraan',
        'nik'                 => 'NIK',
        'no_kk'               => 'No. KK',
        'alamat_jalan'        => 'Alamat',
        'rt'                  => 'RT',
        'rw'                  => 'RW',
        'desa_kode'           => 'Desa/kelurahan',
        'kecamatan_kode'      => 'Kecamatan',
        'kabupaten_kode'      => 'Kabupaten/kota',
        'provinsi_kode'       => 'Provinsi',
        'kode_pos'            => 'Kode pos',
        'no_hp'               => 'No. HP',
        'email'               => 'Email',
        'sembunyikan_kontak'  => 'Sembunyikan kontak',
        'status_hidup'        => 'Status',
        'tanggal_wafat'       => 'Tanggal wafat',
        'tahun_wafat'         => 'Tahun wafat',
        'tempat_makam'        => 'Tempat makam',
        'biografi'            => 'Biografi',
    ];

    /**
     * @var array<string, array{label: string, ikon: string}>
     */
    public array $kategoriBerita = [
        'berita'     => ['label' => 'Berita', 'ikon' => 'bi-newspaper'],
        'pengumuman' => ['label' => 'Pengumuman', 'ikon' => 'bi-megaphone'],
        'sukacita'   => ['label' => 'Sukacita', 'ikon' => 'bi-balloon-heart'],
        'dukacita'   => ['label' => 'Dukacita', 'ikon' => 'bi-flower1'],
    ];

    /**
     * @var array<string, string>
     */
    public array $jenisKegiatan = [
        'pesta_adat'    => 'Pesta Adat',
        'bona_taon'     => 'Pesta Bona Taon',
        'partangiangan' => 'Partangiangan (Ibadah)',
        'arisan'        => 'Arisan / Punguan',
        'rapat'         => 'Rapat Pengurus',
        'sosial'        => 'Kegiatan Sosial',
        'olahraga'      => 'Olahraga & Kebersamaan',
        'lainnya'       => 'Lainnya',
    ];
}
