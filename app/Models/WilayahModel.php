<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class WilayahModel extends Model
{
    public const PROVINSI  = 1;
    public const KABUPATEN = 2;
    public const KECAMATAN = 3;
    public const DESA      = 4;

    protected $table         = 'wilayah';
    protected $primaryKey    = 'kode';
    protected $returnType    = 'array';
    protected $allowedFields = ['kode', 'nama', 'tingkat', 'induk_kode'];

    /**
     * @return list<array{kode: string, nama: string}>
     */
    public function turunan(?string $indukKode): array
    {
        $builder = $this->select('kode, nama')->orderBy('nama', 'ASC');

        return $indukKode === null
            ? $builder->where('tingkat', self::PROVINSI)->findAll()
            : $builder->where('induk_kode', $indukKode)->findAll();
    }

    public static function tingkatDariKode(string $kode): int
    {
        return count(explode('.', $kode));
    }

    public static function indukDariKode(string $kode): ?string
    {
        $pos = strrpos($kode, '.');

        return $pos === false ? null : substr($kode, 0, $pos);
    }
}
