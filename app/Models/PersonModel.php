<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Person;
use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table          = 'persons';
    protected $primaryKey     = 'id';
    protected $returnType     = Person::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'marga_id', 'kode_anggota', 'nomor_urut', 'generasi_ke', 'garis',
        'induk_id', 'ayah_id', 'ibu_id', 'nama_ibu', 'urutan_anak', 'marga_nama',
        'nama_lengkap', 'nama_panggilan', 'gelar_adat', 'jenis_kelamin',
        'tempat_lahir', 'tanggal_lahir', 'tahun_lahir', 'agama', 'status_perkawinan',
        'pendidikan_terakhir', 'pekerjaan', 'golongan_darah', 'kewarganegaraan',
        'nik_enc', 'nik_hash', 'no_kk_enc',
        'alamat_jalan', 'rt', 'rw', 'desa_kode', 'kecamatan_kode', 'kabupaten_kode',
        'provinsi_kode', 'kode_pos', 'no_hp', 'email', 'sembunyikan_kontak',
        'status_hidup', 'tanggal_wafat', 'tahun_wafat', 'tempat_makam',
        'foto', 'biografi',
        'status_data', 'verified_by', 'verified_at', 'created_by', 'updated_by',
    ];

    /**
     * Kolom yang boleh diubah lewat form profil (bukan kolom struktur silsilah).
     *
     * @var list<string>
     */
    public const KOLOM_PROFIL = [
        'nama_lengkap', 'nama_panggilan', 'gelar_adat', 'nama_ibu', 'marga_nama',
        'tempat_lahir', 'tanggal_lahir', 'tahun_lahir', 'agama', 'status_perkawinan',
        'pendidikan_terakhir', 'pekerjaan', 'golongan_darah', 'kewarganegaraan',
        'alamat_jalan', 'rt', 'rw', 'desa_kode', 'kecamatan_kode', 'kabupaten_kode',
        'provinsi_kode', 'kode_pos', 'no_hp', 'email', 'sembunyikan_kontak',
        'status_hidup', 'tanggal_wafat', 'tahun_wafat', 'tempat_makam',
        'foto', 'biografi',
    ];

    public function leluhurAwal(int $margaId): ?Person
    {
        return $this->where('marga_id', $margaId)
            ->where('generasi_ke', 1)
            ->where('garis', 'utama')
            ->first();
    }

    /**
     * @return list<Person>
     */
    public function anak(int $indukId): array
    {
        return $this->where('induk_id', $indukId)
            ->orderBy('urutan_anak', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @return list<Person>
     */
    public function saudaraKandung(Person $person): array
    {
        if ($person->induk_id === null) {
            return [];
        }

        return $this->where('induk_id', $person->induk_id)
            ->where('id !=', $person->id)
            ->orderBy('urutan_anak', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
