<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class KegiatanModel extends Model
{
    protected $table          = 'kegiatan';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'marga_id', 'jenis', 'judul', 'slug', 'deskripsi', 'mulai', 'selesai', 'lokasi', 'alamat',
        'kabupaten_kode', 'peta_url', 'kontak', 'gambar', 'status', 'created_by', 'updated_by',
    ];
    protected $validationRules = [
        'judul'    => ['label' => 'Judul', 'rules' => 'required|max_length[200]'],
        'jenis'    => ['label' => 'Jenis kegiatan', 'rules' => 'required|max_length[30]'],
        'mulai'    => ['label' => 'Waktu mulai', 'rules' => 'required|valid_date[Y-m-d H:i:s]'],
        'selesai'  => ['label' => 'Waktu selesai', 'rules' => 'permit_empty|valid_date[Y-m-d H:i:s]'],
        'lokasi'   => ['label' => 'Lokasi', 'rules' => 'required|max_length[200]'],
        'peta_url' => ['label' => 'Tautan peta', 'rules' => 'permit_empty|valid_url_strict[https]|max_length[500]'],
        'status'   => ['label' => 'Status', 'rules' => 'required|in_list[draft,terbit,batal]'],
    ];

    public function akanDatang(): self
    {
        return $this->whereIn('status', ['terbit', 'batal'])
            ->groupStart()
                ->where('mulai >=', date('Y-m-d 00:00:00'))
                ->orWhere('selesai >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('mulai', 'ASC');
    }

    public function sudahLewat(): self
    {
        return $this->whereIn('status', ['terbit', 'batal'])
            ->where('mulai <', date('Y-m-d 00:00:00'))
            ->groupStart()->where('selesai', null)->orWhere('selesai <', date('Y-m-d H:i:s'))->groupEnd()
            ->orderBy('mulai', 'DESC');
    }
}
