<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BeritaModel extends Model
{
    protected $table          = 'berita';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'marga_id', 'punguan_id', 'kategori', 'judul', 'slug', 'ringkasan', 'isi', 'gambar', 'status', 'terbit_at', 'dilihat', 'created_by', 'updated_by',
    ];
    protected $validationRules = [
        'judul'    => ['label' => 'Judul', 'rules' => 'required|max_length[200]'],
        'kategori' => ['label' => 'Kategori', 'rules' => 'required|in_list[berita,pengumuman,sukacita,dukacita]'],
        'isi'      => ['label' => 'Isi', 'rules' => 'required'],
        'status'   => ['label' => 'Status', 'rules' => 'required|in_list[draft,terbit]'],
    ];

    /**
     * Berita yang sudah terbit (untuk halaman publik).
     */
    public function terbit(?string $kategori = null): self
    {
        $this->where('status', 'terbit')->where('terbit_at <=', date('Y-m-d H:i:s'));
        if ($kategori !== null) {
            $this->where('kategori', $kategori);
        }

        return $this->orderBy('terbit_at', 'DESC');
    }
}
