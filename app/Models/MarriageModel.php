<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class MarriageModel extends Model
{
    protected $table          = 'marriages';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'suami_id', 'istri_id', 'urutan', 'tanggal_nikah', 'tempat_nikah', 'status', 'created_by',
    ];

    public function menikah(int $suamiId, int $istriId): bool
    {
        return $this->where('suami_id', $suamiId)->where('istri_id', $istriId)->countAllResults() > 0;
    }

    /**
     * ID pasangan dari seseorang, berurutan menurut pernikahan.
     *
     * @return list<int>
     */
    public function pasanganIds(int $personId): array
    {
        $rows = $this->groupStart()
            ->where('suami_id', $personId)
            ->orWhere('istri_id', $personId)
            ->groupEnd()
            ->orderBy('urutan', 'ASC')
            ->findAll();

        return array_map(
            static fn (array $m): int => (int) ($m['suami_id'] == $personId ? $m['istri_id'] : $m['suami_id']),
            $rows,
        );
    }
}
