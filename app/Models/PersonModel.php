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

    /**
     * Filter daftar anggota garis marga (publik): generasi, garis, dan kata kunci nama.
     * Hasilnya dipakai dengan paginate().
     */
    public function daftar(int $margaId, ?int $generasi, ?string $garis, ?string $cari): self
    {
        $this->select('persons.id, persons.kode_anggota, persons.nama_lengkap, persons.gelar_adat, persons.generasi_ke,
                persons.garis, persons.jenis_kelamin, persons.status_hidup, persons.tahun_lahir, persons.tahun_wafat,
                induk.nama_lengkap AS nama_induk, induk.id AS induk_id')
            ->join('persons induk', 'induk.id = persons.induk_id', 'left')
            ->where('persons.marga_id', $margaId)
            ->whereIn('persons.garis', $garis !== null && in_array($garis, ['utama', 'boru', 'anak_boru'], true) ? [$garis] : ['utama', 'boru']);

        if ($generasi !== null) {
            $this->where('persons.generasi_ke', $generasi);
        }
        if ($cari !== null && trim($cari) !== '') {
            $this->cariNama($cari);
        }

        return $this->orderBy('persons.generasi_ke', 'ASC')
            ->orderBy('persons.induk_id', 'ASC')
            ->orderBy('persons.urutan_anak', 'ASC');
    }

    /**
     * Pencarian nama: FULLTEXT (awalan kata) untuk kata >= 3 huruf, selain itu LIKE.
     * Kode anggota (mis. PDS-G12-000345) dicari persis.
     */
    public function cariNama(string $cari): self
    {
        $cari = trim($cari);

        if (preg_match('/^[A-Za-z]{2,5}-G\d{2,3}-\d{6}$/', $cari)) {
            return $this->where('persons.kode_anggota', strtoupper($cari));
        }

        $kata = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $cari) ?: [],
            static fn (string $k): bool => mb_strlen($k) >= 3,
        ));

        if ($kata !== [] && $this->db->DBDriver === 'MySQLi') {
            $boolean = implode(' ', array_map(static fn (string $k): string => '+' . $k . '*', $kata));

            return $this->where('MATCH(persons.nama_lengkap, persons.nama_panggilan, persons.gelar_adat) AGAINST(' . $this->db->escape($boolean) . ' IN BOOLEAN MODE)', null, false);
        }

        return $this->groupStart()
            ->like('persons.nama_lengkap', $cari)
            ->orLike('persons.nama_panggilan', $cari)
            ->orLike('persons.gelar_adat', $cari)
            ->groupEnd();
    }
}
