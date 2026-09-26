<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Member Punguan: member marga yang resmi terdaftar di sebuah punguan.
 */
class KeanggotaanPunguanModel extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu pengesahan',
        'aktif'    => 'Aktif',
        'nonaktif' => 'Nonaktif',
        'ditolak'  => 'Ditolak',
    ];

    protected $table         = 'keanggotaan_punguan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'punguan_id', 'person_id', 'nomor_anggota', 'status', 'tanggal_masuk', 'tanggal_keluar',
        'diajukan_oleh', 'disahkan_oleh', 'disahkan_at', 'catatan',
    ];

    /**
     * Keanggotaan yang sedang berlaku (aktif atau menunggu) untuk seseorang.
     *
     * @return array<string, mixed>|null
     */
    public function berlaku(int $personId): ?array
    {
        return $this->select('keanggotaan_punguan.*, punguan.nama AS nama_punguan, punguan.slug AS slug_punguan')
            ->join('punguan', 'punguan.id = keanggotaan_punguan.punguan_id')
            ->where('keanggotaan_punguan.person_id', $personId)
            ->whereIn('keanggotaan_punguan.status', ['aktif', 'menunggu'])
            ->orderBy("FIELD(keanggotaan_punguan.status, 'aktif', 'menunggu')", '', false)
            ->first();
    }

    /**
     * Status keanggotaan untuk banyak orang sekaligus (untuk lencana di daftar).
     *
     * @param list<int> $personIds
     *
     * @return array<int, array{status: string, punguan: string}>
     */
    public function peta(array $personIds): array
    {
        if ($personIds === []) {
            return [];
        }
        $rows = $this->select('keanggotaan_punguan.person_id, keanggotaan_punguan.status, punguan.nama AS punguan')
            ->join('punguan', 'punguan.id = keanggotaan_punguan.punguan_id')
            ->whereIn('keanggotaan_punguan.person_id', $personIds)
            ->whereIn('keanggotaan_punguan.status', ['aktif', 'menunggu'])
            ->findAll();

        $hasil = [];
        foreach ($rows as $r) {
            $pid = (int) $r['person_id'];
            if (! isset($hasil[$pid]) || $r['status'] === 'aktif') {
                $hasil[$pid] = ['status' => $r['status'], 'punguan' => $r['punguan']];
            }
        }

        return $hasil;
    }
}
