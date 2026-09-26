<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class KeuanganCatatanModel extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu validasi',
        'sah'      => 'Sah',
        'ditolak'  => 'Ditolak',
    ];

    public const METODE = ['tunai' => 'Tunai', 'transfer' => 'Transfer bank', 'lainnya' => 'Lainnya'];

    protected $table          = 'keuangan_catatan';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'punguan_id', 'keanggotaan_id', 'person_id', 'kategori_id', 'periode', 'tanggal', 'nominal', 'metode', 'keterangan',
        'status', 'alasan_tolak', 'dicatat_oleh', 'divalidasi_oleh', 'divalidasi_at',
    ];

    /**
     * Catatan beserta nama anggota, kategori, dan pencatat.
     */
    public function lengkap(): self
    {
        return $this->select('keuangan_catatan.*, persons.nama_lengkap, persons.kode_anggota, keuangan_kategori.nama AS kategori, keuangan_kategori.jenis,
                punguan.nama AS nama_punguan, pencatat.username AS pencatat, validator.username AS validator')
            ->join('persons', 'persons.id = keuangan_catatan.person_id')
            ->join('keuangan_kategori', 'keuangan_kategori.id = keuangan_catatan.kategori_id')
            ->join('punguan', 'punguan.id = keuangan_catatan.punguan_id')
            ->join('users pencatat', 'pencatat.id = keuangan_catatan.dicatat_oleh', 'left')
            ->join('users validator', 'validator.id = keuangan_catatan.divalidasi_oleh', 'left');
    }
}
