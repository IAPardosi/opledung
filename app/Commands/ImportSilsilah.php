<?php

declare(strict_types=1);

namespace App\Commands;

use App\Exceptions\SilsilahException;
use App\Models\MargaModel;
use App\Services\ImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Import anggota dari Excel/CSV lewat terminal (untuk file besar).
 * Dijalankan sebagai proses sistem, jadi hanya untuk operator server.
 */
class ImportSilsilah extends BaseCommand
{
    protected $group       = 'Silsilah';
    protected $name        = 'silsilah:import';
    protected $description = 'Import data anggota dari file .xlsx/.csv (lihat docs/PANDUAN-IMPORT.md).';
    protected $usage       = 'silsilah:import <file> --marga <KODE> [--simpan]';
    protected $arguments   = ['file' => 'Path file .xlsx / .xls / .csv'];
    protected $options     = [
        '--marga'  => 'Kode marga tujuan, mis. PDS',
        '--simpan' => 'Simpan ke database. Tanpa opsi ini hanya pratinjau.',
    ];

    public function run(array $params)
    {
        $file  = $params[0] ?? null;
        // spark berjalan dari folder public/, jadi path relatif diacu dari root proyek.
        if ($file !== null && ! str_starts_with($file, '/')) {
            $file = ROOTPATH . $file;
        }
        $kode  = strtoupper((string) (CLI::getOption('marga') ?? ''));
        $marga = (new MargaModel())->where('kode', $kode)->first();

        if ($file === null || ! is_file($file) || $marga === null) {
            CLI::error('Gunakan: php spark ' . $this->usage);

            return EXIT_USER_INPUT;
        }

        $simpan  = CLI::getOption('simpan') !== null;
        $service = new ImportService();

        try {
            $rows  = $service->bacaFile($file);
            $hasil = $service->proses((int) $marga['id'], $rows, null, $simpan);
        } catch (SilsilahException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        foreach ($hasil['laporan'] as $r) {
            if (! $r['ok']) {
                CLI::write("Baris {$r['baris']} ({$r['kode_ref']} {$r['nama']}): {$r['pesan']}", 'red');
            }
        }

        CLI::write("Berhasil: {$hasil['jumlah_ok']}, gagal: {$hasil['jumlah_gagal']}.");
        CLI::write(
            $hasil['disimpan'] ? 'Data tersimpan.' : ($simpan ? 'Import dibatalkan karena ada baris gagal.' : 'Pratinjau selesai, tidak ada data yang disimpan.'),
            $hasil['disimpan'] || (! $simpan && $hasil['berhasil']) ? 'green' : 'yellow',
        );

        return $hasil['berhasil'] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
