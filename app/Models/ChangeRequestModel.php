<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ChangeRequestModel extends Model
{
    protected $table         = 'change_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'marga_id', 'user_id', 'person_id', 'kabupaten_kode', 'jenis', 'payload', 'status',
        'catatan_pengusul', 'catatan_verifikator', 'hasil_person_id', 'reviewed_by', 'reviewed_at',
    ];
    protected array $casts = [
        'payload' => 'json-array',
    ];
}
