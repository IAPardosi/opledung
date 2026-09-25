<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\WilayahModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Mengisi referensi wilayah Indonesia (provinsi s.d. desa/kelurahan).
 *
 * Sumber: Kepmendagri No 300.2.2-2138 Tahun 2025, dari dataset
 * https://github.com/cahyadsn/wilayah (lisensi MIT).
 */
class WilayahSeeder extends Seeder
{
    private const FILE = APPPATH . 'Database/Data/wilayah.tsv.gz';

    public function run(): void
    {
        $handle = gzopen(self::FILE, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File data wilayah tidak ditemukan: ' . self::FILE);
        }

        $this->db->table('wilayah')->truncate();

        $batch  = [];
        $jumlah = 0;
        while (($baris = gzgets($handle)) !== false) {
            $baris = rtrim($baris, "\r\n");
            if ($baris === '') {
                continue;
            }
            [$kode, $nama] = explode("\t", $baris, 2);

            $batch[] = [
                'kode'       => $kode,
                'nama'       => $nama,
                'tingkat'    => WilayahModel::tingkatDariKode($kode),
                'induk_kode' => WilayahModel::indukDariKode($kode),
            ];

            if (count($batch) === 2000) {
                $jumlah += $this->db->table('wilayah')->insertBatch($batch);
                $batch = [];
            }
        }
        gzclose($handle);

        if ($batch !== []) {
            $jumlah += $this->db->table('wilayah')->insertBatch($batch);
        }

        if (is_cli()) {
            echo "  Wilayah: {$jumlah} baris." . PHP_EOL;
        }
    }
}
