<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int         $id
 * @property int         $marga_id
 * @property string      $kode_anggota
 * @property int         $generasi_ke
 * @property string      $garis
 * @property int|null    $induk_id
 * @property int|null    $ayah_id
 * @property int|null    $ibu_id
 * @property string      $nama_lengkap
 * @property string      $jenis_kelamin
 * @property string      $status_data
 */
class Person extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'verified_at'];

    protected $casts = [
        'id'                 => 'integer',
        'marga_id'           => 'integer',
        'nomor_urut'         => 'integer',
        'generasi_ke'        => 'integer',
        'induk_id'           => '?integer',
        'ayah_id'            => '?integer',
        'ibu_id'             => '?integer',
        'urutan_anak'        => '?integer',
        'tahun_lahir'        => '?integer',
        'tahun_wafat'        => '?integer',
        'sembunyikan_kontak' => 'boolean',
    ];

    /**
     * Field terenkripsi tidak pernah ikut diserialisasi ke tampilan/JSON.
     */
    public function toPublicArray(): array
    {
        $data = $this->toArray();
        unset($data['nik_enc'], $data['nik_hash'], $data['no_kk_enc']);

        return $data;
    }

    /**
     * Hanya garis utama dan boru yang boleh ditambahkan anak.
     * Anak dari boru adalah ujung cabang (docs/STANDAR.md 3.2).
     */
    public function bisaPunyaAnak(): bool
    {
        return in_array($this->garis, ['utama', 'boru'], true);
    }

    public function isAnggotaGarisMarga(): bool
    {
        return in_array($this->garis, ['utama', 'boru'], true);
    }

    public function isTerkunci(): bool
    {
        return $this->status_data === 'terkunci';
    }

    public function isHidup(): bool
    {
        return $this->attributes['status_hidup'] === 'hidup';
    }

    /**
     * Tanggal lahir lengkap bila ada, atau tahunnya saja.
     */
    public function lahirTampil(): ?string
    {
        return $this->attributes['tanggal_lahir'] ?? ($this->attributes['tahun_lahir'] ?? null);
    }
}
