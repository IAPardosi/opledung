<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLogModel;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Mencatat setiap perubahan data ke audit_logs.
 */
class AuditLogger
{
    /**
     * Kolom yang tidak pernah disimpan ke log karena berisi data pribadi.
     */
    private const KOLOM_RAHASIA = ['nik_enc', 'nik_hash', 'no_kk_enc'];

    public function __construct(private readonly AuditLogModel $model = new AuditLogModel())
    {
    }

    /**
     * @param array<string, mixed>|null $lama
     * @param array<string, mixed>|null $baru
     */
    public function catat(string $aksi, string $tabel, ?int $recordId, ?array $lama, ?array $baru, ?int $userId): void
    {
        $request = service('request');

        $this->model->insert([
            'user_id'    => $userId,
            'aksi'       => $aksi,
            'tabel'      => $tabel,
            'record_id'  => $recordId,
            'data_lama'  => $lama === null ? null : json_encode($this->bersihkan($lama), JSON_UNESCAPED_UNICODE),
            'data_baru'  => $baru === null ? null : json_encode($this->bersihkan($baru), JSON_UNESCAPED_UNICODE),
            'ip_address' => $request instanceof IncomingRequest ? $request->getIPAddress() : null,
            'user_agent' => $request instanceof IncomingRequest ? mb_substr((string) $request->getUserAgent(), 0, 255) : null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function bersihkan(array $data): array
    {
        foreach (self::KOLOM_RAHASIA as $kolom) {
            if (array_key_exists($kolom, $data)) {
                $data[$kolom] = $data[$kolom] === null ? null : '[disamarkan]';
            }
        }

        return $data;
    }
}
