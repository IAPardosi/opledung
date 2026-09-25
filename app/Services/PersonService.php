<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\MarriageModel;
use App\Models\PersonModel;
use App\Models\WilayahModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Config\Database;
use Config\Silsilah;
use Throwable;

/**
 * Semua perubahan data silsilah melewati service ini agar aturan adat,
 * closure table (person_paths), hak akses, dan audit log selalu konsisten.
 */
class PersonService
{
    private readonly BaseConnection $db;

    public function __construct(
        private readonly PersonModel $persons = new PersonModel(),
        private readonly MarriageModel $marriages = new MarriageModel(),
        private SilsilahPolicy $policy = new SilsilahPolicy(),
        private readonly AuditLogger $audit = new AuditLogger(),
        private readonly DataPribadiCipher $cipher = new DataPribadiCipher(),
        private readonly Silsilah $config = new Silsilah(),
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Salinan service untuk menjalankan usulan yang lingkup Admin Wilayah-nya
     * sudah diperiksa UsulanService.
     */
    public function untukUsulanTerverifikasi(): static
    {
        $salinan         = clone $this;
        $salinan->policy = $this->policy->tanpaCekLingkup();

        return $salinan;
    }

    /**
     * Menetapkan Generasi 1 (leluhur awal) sebuah marga. Hanya Ketua Adat/Super Admin.
     *
     * @param array<string, mixed> $data
     */
    public function tambahLeluhurAwal(int $margaId, array $data, ?User $aktor): Person
    {
        $this->policy->pastikan(
            $this->policy->bolehKelolaGenerasi($aktor, $margaId, 1),
            'Hanya Ketua Adat yang dapat menetapkan leluhur awal (Generasi 1).',
        );

        if ($this->persons->leluhurAwal($margaId) !== null) {
            throw new SilsilahException('Leluhur awal (Generasi 1) marga ini sudah ditetapkan.');
        }

        $data['jenis_kelamin'] ??= 'L';
        if ($data['jenis_kelamin'] !== 'L') {
            throw new SilsilahException('Leluhur awal garis marga harus laki-laki.');
        }

        $data = $this->siapkanData($data, true);

        return $this->simpanBaru($margaId, 1, 'utama', null, $data, $aktor);
    }

    /**
     * Menambah anak di bawah anggota garis utama atau boru.
     *
     * - Induk garis utama: anak laki-laki → garis utama, anak perempuan → boru.
     * - Induk boru: anak → anak_boru (ujung cabang, tidak diteruskan).
     *
     * Data boleh berisi 'pasangan_id' (ibu/ayah dari anak, harus pasangan induk).
     *
     * @param array<string, mixed> $data
     */
    public function tambahAnak(int $indukId, array $data, ?User $aktor): Person
    {
        $induk = $this->ambil($indukId);

        if (! $induk->bisaPunyaAnak()) {
            throw new SilsilahException(
                'Keturunan dari anak boru dan pasangan tidak diteruskan dalam silsilah marga.',
            );
        }

        $generasi = $induk->generasi_ke + 1;
        $this->policy->pastikan(
            $this->policy->bolehKelolaGenerasi($aktor, $induk->marga_id, $generasi, $induk),
            $this->policy->isSilsilahPokok($induk->marga_id, $generasi)
                ? "Generasi {$generasi} termasuk Silsilah Pokok dan hanya dapat diisi oleh Ketua Adat."
                : 'Anda tidak memiliki hak untuk menambah anggota. Silakan ajukan usulan data.',
        );

        $pasanganId = isset($data['pasangan_id']) && $data['pasangan_id'] !== '' ? (int) $data['pasangan_id'] : null;
        unset($data['pasangan_id']);

        $data = $this->siapkanData($data, true);

        if ($induk->garis === 'utama') {
            $garis           = $data['jenis_kelamin'] === 'L' ? 'utama' : 'boru';
            $data['ayah_id'] = $induk->id;
            $data['ibu_id']  = null;
            if ($pasanganId !== null) {
                $this->pastikanPasangan($induk->id, $pasanganId);
                $data['ibu_id'] = $pasanganId;
            }
            unset($data['marga_nama']);
        } else {
            $garis          = 'anak_boru';
            $data['ibu_id'] = $induk->id;
            $data['ayah_id'] = null;
            if ($pasanganId !== null) {
                $suami = $this->pastikanPasangan($pasanganId, $induk->id);
                $data['ayah_id'] = $suami->id;
                $data['marga_nama'] ??= $suami->marga_nama;
            }
        }

        $data['urutan_anak'] ??= $this->urutanAnakBerikutnya($induk->id);

        return $this->simpanBaru($induk->marga_id, $generasi, $garis, $induk, $data, $aktor);
    }

    /**
     * Menambah pasangan: istri untuk garis utama, suami untuk boru.
     *
     * @param array<string, mixed> $data           data pribadi pasangan (wajib marga_nama)
     * @param array<string, mixed> $dataPernikahan tanggal_nikah, tempat_nikah, status
     */
    public function tambahPasangan(int $personId, array $data, array $dataPernikahan, ?User $aktor): Person
    {
        $person = $this->ambil($personId);

        if (! $person->isAnggotaGarisMarga()) {
            throw new SilsilahException('Pasangan hanya dapat ditambahkan untuk anggota garis utama atau boru.');
        }

        $this->policy->pastikan($this->policy->bolehKelolaGenerasi($aktor, $person->marga_id, $person->generasi_ke, $person));

        $data['jenis_kelamin'] = $person->jenis_kelamin === 'L' ? 'P' : 'L';
        $data = $this->siapkanData($data, true);

        if (empty($data['marga_nama'])) {
            throw new ValidasiDataException(['marga_nama' => 'Marga pasangan wajib diisi.']);
        }

        $this->db->transException(true)->transStart();

        try {
            $pasangan = $this->simpanBaru($person->marga_id, $person->generasi_ke, 'pasangan', null, $data, $aktor, false);

            [$suamiId, $istriId] = $person->jenis_kelamin === 'L'
                ? [$person->id, $pasangan->id]
                : [$pasangan->id, $person->id];

            $pernikahan = [
                'suami_id'      => $suamiId,
                'istri_id'      => $istriId,
                'urutan'        => $this->marriages->where($person->jenis_kelamin === 'L' ? 'suami_id' : 'istri_id', $person->id)->countAllResults() + 1,
                'tanggal_nikah' => $this->kosongJadiNull($dataPernikahan['tanggal_nikah'] ?? null),
                'tempat_nikah'  => $this->kosongJadiNull($dataPernikahan['tempat_nikah'] ?? null),
                'status'        => $dataPernikahan['status'] ?? 'menikah',
                'created_by'    => $aktor?->id,
            ];
            $marriageId = (int) $this->marriages->insert($pernikahan);
            $this->audit->catat('tambah', 'marriages', $marriageId, null, $pernikahan, $aktor?->id);

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $pasangan;
    }

    /**
     * Mengubah data profil (bukan struktur silsilah).
     *
     * @param array<string, mixed> $data
     */
    public function ubahProfil(int $personId, array $data, ?User $aktor): Person
    {
        $person = $this->ambil($personId);
        $this->policy->pastikan(
            $this->policy->bolehUbahProfil($aktor, $person),
            $person->isTerkunci()
                ? 'Data ini sudah dikunci Ketua Adat dan tidak dapat diubah.'
                : 'Anda tidak memiliki hak untuk mengubah data ini. Silakan ajukan usulan data.',
        );

        $izin = [...PersonModel::KOLOM_PROFIL, 'nik', 'no_kk'];
        $data = array_intersect_key($data, array_flip($izin));
        $data = $this->siapkanData($data, false, $person->id);

        if ($data === []) {
            return $person;
        }

        $lama = array_intersect_key($person->toRawArray(), $data);
        $data['updated_by'] = $aktor?->id;

        $this->persons->update($person->id, $data);
        $this->audit->catat('ubah', 'persons', $person->id, $lama, $data, $aktor?->id);

        return $this->ambil($person->id);
    }

    /**
     * Validasi data oleh Ketua Adat (Silsilah Pokok → terkunci) atau Verifikator (→ terverifikasi).
     */
    public function validasi(int $personId, ?User $aktor): Person
    {
        $person = $this->ambil($personId);
        $this->policy->pastikan($this->policy->bolehValidasi($aktor, $person));

        $status = $this->policy->isSilsilahPokok($person->marga_id, $person->generasi_ke) ? 'terkunci' : 'terverifikasi';
        $data   = [
            'status_data' => $status,
            'verified_by' => $aktor?->id,
            'verified_at' => Time::now()->toDateTimeString(),
        ];

        $this->persons->update($person->id, $data);
        $this->audit->catat('validasi', 'persons', $person->id, ['status_data' => $person->status_data], $data, $aktor?->id);

        return $this->ambil($person->id);
    }

    /**
     * Soft delete. Tidak boleh bila masih memiliki anak.
     */
    public function hapus(int $personId, ?User $aktor): void
    {
        $person = $this->ambil($personId);
        $this->policy->pastikan(
            $this->policy->bolehKelolaGenerasi($aktor, $person->marga_id, $person->generasi_ke, $person)
            && (! $person->isTerkunci() || $this->policy->bolehValidasi($aktor, $person)),
        );

        $jumlahAnak = $this->persons->where('induk_id', $person->id)->countAllResults()
            + $this->persons->groupStart()->where('ayah_id', $person->id)->orWhere('ibu_id', $person->id)->groupEnd()->countAllResults();
        if ($jumlahAnak > 0) {
            throw new SilsilahException('Data tidak dapat dihapus karena masih memiliki anak dalam silsilah.');
        }

        $this->db->transException(true)->transStart();

        try {
            $this->marriages->groupStart()
                ->where('suami_id', $person->id)
                ->orWhere('istri_id', $person->id)
                ->groupEnd()
                ->delete();
            $this->persons->delete($person->id);
            $this->audit->catat('hapus', 'persons', $person->id, $person->toRawArray(), null, $aktor?->id);

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Memeriksa data orang baru tanpa menyimpan (mis. saat pendaftaran diajukan).
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidasiDataException
     */
    public function periksaData(array $data): void
    {
        $this->siapkanData($data, true);
    }

    public function ambil(int $personId): Person
    {
        $person = $this->persons->find($personId);
        if ($person === null) {
            throw new SilsilahException('Data anggota tidak ditemukan.');
        }

        return $person;
    }

    /**
     * Insert orang baru beserta kode anggota, closure table, dan audit log.
     *
     * @param array<string, mixed> $data
     */
    private function simpanBaru(
        int $margaId,
        int $generasi,
        string $garis,
        ?Person $induk,
        array $data,
        ?User $aktor,
        bool $transaksiSendiri = true,
    ): Person {
        if ($transaksiSendiri) {
            $this->db->transException(true)->transStart();
        }

        try {
            // Mengunci baris marga agar nomor urut tidak bentrok saat input bersamaan.
            $marga = $this->db->query('SELECT id, kode FROM marga WHERE id = ? FOR UPDATE', [$margaId])->getRowArray();
            if ($marga === null) {
                throw new SilsilahException('Marga tidak ditemukan.');
            }

            $nomor = (int) $this->db->table('persons')->selectMax('nomor_urut')->where('marga_id', $margaId)->get()->getRow()->nomor_urut + 1;

            $data = [
                ...$data,
                'marga_id'     => $margaId,
                'generasi_ke'  => $generasi,
                'garis'        => $garis,
                'induk_id'     => $induk?->id,
                'nomor_urut'   => $nomor,
                'kode_anggota' => sprintf('%s-G%02d-%06d', $marga['kode'], $generasi, $nomor),
                'status_data'  => 'draft',
                'created_by'   => $aktor?->id,
                'updated_by'   => $aktor?->id,
            ];

            $id = (int) $this->persons->insert($data);

            if ($garis !== 'pasangan') {
                $this->tambahJalur($id, $induk?->id);
            }

            $this->audit->catat('tambah', 'persons', $id, null, $data, $aktor?->id);

            if ($transaksiSendiri) {
                $this->db->transComplete();
            }
        } catch (Throwable $e) {
            if ($transaksiSendiri) {
                $this->db->transRollback();
            }

            throw $e;
        }

        return $this->ambil($id);
    }

    /**
     * Closure table: salin semua leluhur induk (+1 jarak) lalu tambahkan baris diri sendiri.
     */
    private function tambahJalur(int $id, ?int $indukId): void
    {
        if ($indukId !== null) {
            $this->db->query(
                'INSERT INTO person_paths (ancestor_id, descendant_id, depth)
                 SELECT ancestor_id, ?, depth + 1 FROM person_paths WHERE descendant_id = ?',
                [$id, $indukId],
            );
        }

        $this->db->table('person_paths')->insert(['ancestor_id' => $id, 'descendant_id' => $id, 'depth' => 0]);
    }

    private function pastikanPasangan(int $suamiId, int $istriId): Person
    {
        if (! $this->marriages->menikah($suamiId, $istriId)) {
            throw new SilsilahException('Orang tua yang dipilih bukan pasangan yang tercatat.');
        }

        return $this->ambil($suamiId);
    }

    private function urutanAnakBerikutnya(int $indukId): int
    {
        $row = $this->db->table('persons')->selectMax('urutan_anak')->where('induk_id', $indukId)->get()->getRow();

        return (int) $row->urutan_anak + 1;
    }

    /**
     * Membersihkan, memvalidasi, dan menormalkan input data pribadi.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function siapkanData(array $data, bool $baru, ?int $abaikanId = null): array
    {
        $data = array_map(fn ($v) => is_string($v) ? $this->kosongJadiNull(trim($v)) : $v, $data);

        $validation = service('validation', null, false);
        $validation->setRules($this->aturanValidasi($baru, $data));
        if (! $validation->run($data)) {
            throw new ValidasiDataException($validation->getErrors());
        }

        if (isset($data['tanggal_lahir'])) {
            $data['tahun_lahir'] = (int) substr((string) $data['tanggal_lahir'], 0, 4);
        }
        if (isset($data['tanggal_wafat'])) {
            $data['tahun_wafat']  = (int) substr((string) $data['tanggal_wafat'], 0, 4);
            $data['status_hidup'] = 'meninggal';
        }
        if (isset($data['sembunyikan_kontak'])) {
            $data['sembunyikan_kontak'] = (int) (bool) $data['sembunyikan_kontak'];
        }

        $data = $this->normalkanWilayah($data);

        if (array_key_exists('nik', $data)) {
            $hash = $this->cipher->hash($data['nik']);
            if ($hash !== null) {
                $duplikat = $this->persons->where('nik_hash', $hash);
                if ($abaikanId !== null) {
                    $duplikat->where('id !=', $abaikanId);
                }
                if ($duplikat->countAllResults() > 0) {
                    throw new ValidasiDataException(['nik' => 'NIK sudah terdaftar pada anggota lain.']);
                }
            }
            $data['nik_enc']  = $this->cipher->enkripsi($data['nik']);
            $data['nik_hash'] = $hash;
            unset($data['nik']);
        }
        if (array_key_exists('no_kk', $data)) {
            $data['no_kk_enc'] = $this->cipher->enkripsi($data['no_kk']);
            unset($data['no_kk']);
        }

        return array_intersect_key($data, array_flip([
            ...PersonModel::KOLOM_PROFIL, 'jenis_kelamin', 'urutan_anak',
            'nik_enc', 'nik_hash', 'no_kk_enc',
        ]));
    }

    /**
     * Kode wilayah paling rinci menentukan kode induknya (desa → kecamatan → kab/kota → provinsi).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalkanWilayah(array $data): array
    {
        $kolom = ['desa_kode', 'kecamatan_kode', 'kabupaten_kode', 'provinsi_kode'];
        $rinci = null;
        foreach ($kolom as $k) {
            if (! empty($data[$k])) {
                $rinci = $data[$k];
                break;
            }
        }
        if ($rinci === null) {
            return $data;
        }

        $wilayah = new WilayahModel();
        if ($wilayah->find($rinci) === null) {
            throw new ValidasiDataException(['wilayah' => 'Kode wilayah tidak dikenal.']);
        }

        $tingkat = WilayahModel::tingkatDariKode($rinci);
        $kode    = $rinci;
        // kolom[0] = desa (tingkat 4) ... kolom[3] = provinsi (tingkat 1)
        for ($t = $tingkat; $t >= 1; $t--) {
            $data[$kolom[4 - $t]] = $kode;
            $kode = WilayahModel::indukDariKode($kode);
        }
        for ($t = $tingkat + 1; $t <= 4; $t++) {
            $data[$kolom[4 - $t]] = null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, array<string, string>|string>
     */
    private function aturanValidasi(bool $baru, array $data): array
    {
        $c        = $this->config;
        $wajib    = $baru ? 'required' : 'permit_empty';
        $tahunIni = (int) date('Y');
        $aturan   = [
            'nama_lengkap'        => ['label' => 'Nama lengkap', 'rules' => 'required|max_length[150]'],
            'jenis_kelamin'       => ['label' => 'Jenis kelamin', 'rules' => "{$wajib}|in_list[L,P]"],
            'nama_panggilan'      => ['label' => 'Nama panggilan', 'rules' => 'permit_empty|max_length[100]'],
            'gelar_adat'          => ['label' => 'Gelar adat', 'rules' => 'permit_empty|max_length[150]'],
            'nama_ibu'            => ['label' => 'Nama ibu', 'rules' => 'permit_empty|max_length[150]'],
            'marga_nama'          => ['label' => 'Marga', 'rules' => 'permit_empty|max_length[100]'],
            'urutan_anak'         => ['label' => 'Anak ke-', 'rules' => 'permit_empty|is_natural_no_zero|less_than[100]'],
            'tempat_lahir'        => ['label' => 'Tempat lahir', 'rules' => 'permit_empty|max_length[100]'],
            'tanggal_lahir'       => ['label' => 'Tanggal lahir', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'tahun_lahir'         => ['label' => 'Tahun lahir', 'rules' => "permit_empty|integer|greater_than[1000]|less_than_equal_to[{$tahunIni}]"],
            'agama'               => ['label' => 'Agama', 'rules' => 'permit_empty|in_list[' . implode(',', $c->agama) . ']'],
            'status_perkawinan'   => ['label' => 'Status perkawinan', 'rules' => 'permit_empty|in_list[' . implode(',', $c->statusPerkawinan) . ']'],
            'pendidikan_terakhir' => ['label' => 'Pendidikan terakhir', 'rules' => 'permit_empty|in_list[' . implode(',', $c->pendidikan) . ']'],
            'pekerjaan'           => ['label' => 'Pekerjaan', 'rules' => 'permit_empty|max_length[100]'],
            'golongan_darah'      => ['label' => 'Golongan darah', 'rules' => 'permit_empty|in_list[' . implode(',', $c->golonganDarah) . ']'],
            'kewarganegaraan'     => ['label' => 'Kewarganegaraan', 'rules' => 'permit_empty|in_list[' . implode(',', $c->kewarganegaraan) . ']'],
            'nik'                 => ['label' => 'NIK', 'rules' => 'permit_empty|numeric|exact_length[16]'],
            'no_kk'               => ['label' => 'No. KK', 'rules' => 'permit_empty|numeric|exact_length[16]'],
            'alamat_jalan'        => ['label' => 'Alamat', 'rules' => 'permit_empty|max_length[255]'],
            'rt'                  => ['label' => 'RT', 'rules' => 'permit_empty|numeric|max_length[3]'],
            'rw'                  => ['label' => 'RW', 'rules' => 'permit_empty|numeric|max_length[3]'],
            'kode_pos'            => ['label' => 'Kode pos', 'rules' => 'permit_empty|numeric|exact_length[5]'],
            'no_hp'               => ['label' => 'No. HP', 'rules' => 'permit_empty|regex_match[/^(\+62|62|0)8[0-9]{7,12}$/]'],
            'email'               => ['label' => 'Email', 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'status_hidup'        => ['label' => 'Status hidup', 'rules' => 'permit_empty|in_list[' . implode(',', array_keys($c->statusHidup)) . ']'],
            'tanggal_wafat'       => ['label' => 'Tanggal wafat', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'tahun_wafat'         => ['label' => 'Tahun wafat', 'rules' => "permit_empty|integer|greater_than[1000]|less_than_equal_to[{$tahunIni}]"],
            'tempat_makam'        => ['label' => 'Tempat makam', 'rules' => 'permit_empty|max_length[255]'],
            'biografi'            => ['label' => 'Biografi', 'rules' => 'permit_empty|max_length[20000]'],
        ];

        // Pada ubah profil, hanya field yang dikirim yang divalidasi.
        return $baru ? $aturan : array_intersect_key($aturan, $data);
    }

    private function kosongJadiNull(mixed $nilai): mixed
    {
        return $nilai === '' ? null : $nilai;
    }
}
