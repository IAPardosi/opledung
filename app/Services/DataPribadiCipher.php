<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Encryption\EncrypterInterface;
use Config\Encryption;
use RuntimeException;

/**
 * Enkripsi NIK/No. KK (UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi).
 *
 * Nilai disimpan terenkripsi; untuk cek duplikat NIK dipakai HMAC-SHA256
 * sehingga NIK asli tidak perlu didekripsi saat pencarian.
 */
class DataPribadiCipher
{
    private readonly EncrypterInterface $encrypter;
    private readonly string $kunci;

    public function __construct(?Encryption $config = null)
    {
        $config ??= config(Encryption::class);

        if ($config->key === '') {
            throw new RuntimeException('encryption.key belum diatur di .env. Jalankan: php spark key:generate');
        }

        $this->kunci     = $config->key;
        $this->encrypter = service('encrypter', $config, false);
    }

    public function enkripsi(?string $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return base64_encode($this->encrypter->encrypt($nilai));
    }

    public function dekripsi(?string $sandi): ?string
    {
        if ($sandi === null || $sandi === '') {
            return null;
        }

        return $this->encrypter->decrypt(base64_decode($sandi, true));
    }

    public function hash(?string $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return hash_hmac('sha256', $nilai, $this->kunci);
    }

    /**
     * Contoh: 1271xxxxxxxx0001 → 1271********0001
     */
    public static function samarkan(?string $nilai): ?string
    {
        if ($nilai === null || strlen($nilai) < 8) {
            return $nilai;
        }

        return substr($nilai, 0, 4) . str_repeat('*', strlen($nilai) - 8) . substr($nilai, -4);
    }
}
