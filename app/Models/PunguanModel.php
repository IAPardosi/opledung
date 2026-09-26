<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class PunguanModel extends Model
{
    public const TINGKAT = [
        'pusat'  => 'Pusat',
        'daerah' => 'Daerah',
        'global' => 'Global (luar negeri)',
    ];

    protected $table         = 'punguan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'marga_id', 'induk_id', 'nama', 'slug', 'tingkat', 'wilayah_kode', 'negara', 'keterangan', 'kontak', 'is_active',
    ];
    protected $validationRules = [
        'nama'    => ['label' => 'Nama punguan', 'rules' => 'required|max_length[120]'],
        'tingkat' => ['label' => 'Tingkat', 'rules' => 'required|in_list[pusat,daerah,global]'],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function aktif(int $margaId): array
    {
        return $this->where('marga_id', $margaId)->where('is_active', 1)
            ->orderBy("FIELD(tingkat, 'pusat', 'daerah', 'global')", '', false)
            ->orderBy('nama')
            ->findAll();
    }
}
