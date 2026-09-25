<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class MargaModel extends Model
{
    protected $table          = 'marga';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'kode', 'nama', 'nama_rumpun', 'asal_kampung', 'asal_wilayah_kode',
        'sejarah', 'batas_silsilah_pokok', 'is_active',
    ];
    protected $validationRules = [
        'kode'                 => 'required|alpha|min_length[2]|max_length[5]|is_unique[marga.kode,id,{id}]',
        'nama'                 => 'required|max_length[100]|is_unique[marga.nama,id,{id}]',
        'batas_silsilah_pokok' => 'required|is_natural_no_zero|less_than_equal_to[100]',
    ];
    protected $beforeInsert = ['kodeHurufBesar'];
    protected $beforeUpdate = ['kodeHurufBesar'];

    protected function kodeHurufBesar(array $data): array
    {
        if (isset($data['data']['kode'])) {
            $data['data']['kode'] = strtoupper($data['data']['kode']);
        }

        return $data;
    }
}
