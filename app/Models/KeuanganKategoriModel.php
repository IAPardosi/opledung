<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class KeuanganKategoriModel extends Model
{
    public const JENIS = [
        'bulanan' => 'Bulanan (per bulan)',
        'tahunan' => 'Tahunan (per tahun)',
        'sekali'  => 'Per peristiwa',
    ];

    protected $table         = 'keuangan_kategori';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['punguan_id', 'nama', 'jenis', 'nominal_standar', 'keterangan', 'urutan', 'is_active'];
    protected $validationRules = [
        'nama'            => ['label' => 'Nama kategori', 'rules' => 'required|max_length[80]'],
        'jenis'           => ['label' => 'Jenis', 'rules' => 'required|in_list[bulanan,tahunan,sekali]'],
        'nominal_standar' => ['label' => 'Nominal standar', 'rules' => 'permit_empty|integer|greater_than_equal_to[0]'],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function milik(int $punguanId, bool $hanyaAktif = true): array
    {
        $this->where('punguan_id', $punguanId);
        if ($hanyaAktif) {
            $this->where('is_active', 1);
        }

        return $this->orderBy('urutan')->orderBy('nama')->findAll();
    }
}
