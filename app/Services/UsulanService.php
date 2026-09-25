<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Exceptions\AksesDitolakException;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\ChangeRequestModel;
use App\Models\PersonModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Config\Database;
use Throwable;

/**
 * Usulan data dan pendaftaran anggota (pending → disetujui/ditolak), docs/STANDAR.md bagian 4.
 *
 * Validasi berlapis:
 *  1. Kesaksian keluarga: kerabat dekat yang sudah terverifikasi menyatakan benar/salah.
 *  2. Admin Wilayah (sesuai wilayah domisili atau cabang pomparan) atau Admin Marga memutuskan.
 *  3. Silsilah Pokok (G1–batas) tetap hanya lewat Ketua Adat.
 *
 * Persetujuan dijalankan lewat PersonService dengan verifikator sebagai aktor.
 */
class UsulanService
{
    public const JENIS = [
        'daftar_anggota'  => 'Pendaftaran anggota',
        'tambah_anak'     => 'Tambah anak',
        'tambah_pasangan' => 'Tambah pasangan',
        'ubah_data'       => 'Ubah data',
        'klaim_profil'    => 'Klaim profil ("Ini saya")',
    ];

    /**
     * Jumlah sundut di atas leluhur pilihan yang masih dihitung "keluarga dekat" untuk kesaksian.
     */
    public const JARAK_SAKSI = 3;

    /**
     * Maksimal generasi antara yang boleh diisi sendiri saat mendaftar.
     */
    public const MAKS_ANTARA = 6;

    private const FIELD_RAHASIA = ['nik', 'no_kk'];

    public function __construct(
        private readonly ChangeRequestModel $model = new ChangeRequestModel(),
        private readonly PersonService $persons = new PersonService(),
        private readonly SilsilahPolicy $policy = new SilsilahPolicy(),
        private readonly DataPribadiCipher $cipher = new DataPribadiCipher(),
        private readonly AuditLogger $audit = new AuditLogger(),
        private readonly LingkupAdmin $lingkup = new LingkupAdmin(),
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function ajukan(User $user, string $jenis, Person $person, array $payload, ?string $catatan = null): int
    {
        if (! isset(self::JENIS[$jenis]) || $jenis === 'daftar_anggota') {
            throw new SilsilahException('Jenis usulan tidak dikenal.');
        }
        $bolehKlaimCalon = $jenis === 'klaim_profil' && $user->inGroup('calon');
        if (! $user->can('silsilah.propose') && ! $user->inGroup('superadmin') && ! $bolehKlaimCalon) {
            throw new AksesDitolakException('Anda tidak memiliki hak untuk mengajukan usulan.');
        }
        if ($user->marga_id === null || (int) $user->marga_id !== $person->marga_id) {
            throw new AksesDitolakException('Usulan hanya dapat diajukan untuk marga Anda sendiri.');
        }

        if ($jenis === 'klaim_profil') {
            $this->pastikanBisaDiklaim($user, $person);
            $this->pastikanTidakAdaPendaftaranPending($user);
            $payload = [];
        }

        $sudahAda = $this->model->where('user_id', $user->id)
            ->where('person_id', $person->id)
            ->where('jenis', $jenis)
            ->where('status', 'pending')
            ->countAllResults();
        if ($sudahAda > 0 && $jenis !== 'tambah_anak') {
            throw new SilsilahException('Usulan yang sama masih menunggu verifikasi.');
        }

        return $this->simpanUsulan($user, $jenis, $person, $this->kunciPayload($payload), $catatan, $this->domisiliPengusul($user));
    }

    /**
     * Pendaftaran anggota baru beserta silsilahnya.
     *
     * @param list<array<string, mixed>> $antara  generasi antara, dari anak leluhur sampai ayah pendaftar
     * @param array<string, mixed>       $dataDiri data pribadi pendaftar
     */
    public function ajukanPendaftaran(User $user, Person $leluhur, array $antara, array $dataDiri, ?string $catatan = null): int
    {
        if ($user->person_id !== null) {
            throw new SilsilahException('Akun Anda sudah tertaut ke data silsilah.');
        }
        $this->pastikanTidakAdaPendaftaranPending($user);

        $antara = array_values(array_filter($antara, static fn (array $a): bool => trim((string) ($a['nama_lengkap'] ?? '')) !== ''));
        $this->periksaSilsilahPendaftaran($leluhur, $antara);

        $wajib = [];
        if (trim((string) ($dataDiri['nama_lengkap'] ?? '')) === '') {
            $wajib['nama_lengkap'] = 'Nama lengkap wajib diisi.';
        }
        if (! in_array($dataDiri['jenis_kelamin'] ?? null, ['L', 'P'], true)) {
            $wajib['jenis_kelamin'] = 'Jenis kelamin wajib dipilih.';
        }
        if (empty($dataDiri['kabupaten_kode']) && empty($dataDiri['desa_kode']) && empty($dataDiri['kecamatan_kode'])) {
            $wajib['kabupaten_kode'] = 'Kabupaten/kota domisili wajib dipilih agar diteruskan ke Admin Wilayah.';
        }
        if ($wajib !== []) {
            throw new ValidasiDataException($wajib);
        }

        $this->persons->periksaData($dataDiri);
        $kabupaten = $this->kabupatenDari($dataDiri);

        // Marga akun mengikuti marga leluhur yang dipilih.
        if ($user->marga_id === null || (int) $user->marga_id !== $leluhur->marga_id) {
            (new UserModel())->update($user->id, ['marga_id' => $leluhur->marga_id]);
            $user->marga_id = $leluhur->marga_id;
        }

        return $this->simpanUsulan(
            $user,
            'daftar_anggota',
            $leluhur,
            $this->kunciPayload(['antara' => $antara, 'data' => $dataDiri]),
            $catatan,
            $kabupaten,
        );
    }

    /**
     * Generasi pendaftar dan ringkasan jalurnya, untuk pratinjau di form.
     *
     * @param list<array<string, mixed>> $antara
     */
    public function generasiPendaftar(Person $leluhur, array $antara): int
    {
        return $leluhur->generasi_ke + count($antara) + 1;
    }

    /**
     * Kesaksian kerabat atas sebuah usulan.
     */
    public function konfirmasi(int $id, User $user, bool $benar, ?string $catatan = null): void
    {
        $usulan = $this->model->find($id);
        if ($usulan === null || $usulan['status'] !== 'pending') {
            throw new SilsilahException('Usulan tidak ditemukan atau sudah diproses.');
        }
        if ((int) $usulan['user_id'] === $user->id) {
            throw new SilsilahException('Anda tidak dapat menjadi saksi atas usulan sendiri.');
        }
        if (! $this->bolehBersaksi($user, $usulan)) {
            throw new AksesDitolakException('Hanya kerabat dekat yang sudah terverifikasi yang dapat menjadi saksi.');
        }

        $db = Database::connect();
        $db->table('konfirmasi_keluarga')->where(['change_request_id' => $id, 'user_id' => $user->id])->delete();
        $db->table('konfirmasi_keluarga')->insert([
            'change_request_id' => $id,
            'user_id'           => $user->id,
            'person_id'         => $user->person_id,
            'benar'             => $benar ? 1 : 0,
            'catatan'           => $catatan !== null ? mb_substr(trim($catatan), 0, 500) : null,
            'created_at'        => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * Usulan yang menunggu kesaksian dari pengguna ini (kerabat dekat).
     *
     * @return list<array<string, mixed>>
     */
    public function menungguKesaksian(User $user): array
    {
        if ($user->person_id === null || ! $user->can('silsilah.view')) {
            return [];
        }

        $rows = $this->model
            ->select('change_requests.*, persons.nama_lengkap AS nama_leluhur, persons.generasi_ke AS generasi_leluhur, users.username,
                (SELECT k.benar FROM konfirmasi_keluarga k WHERE k.change_request_id = change_requests.id AND k.user_id = ' . (int) $user->id . ') AS kesaksian_saya', false)
            ->join('persons', 'persons.id = change_requests.person_id')
            ->join('users', 'users.id = change_requests.user_id')
            ->where('change_requests.status', 'pending')
            ->whereIn('change_requests.jenis', ['daftar_anggota', 'tambah_anak', 'klaim_profil'])
            ->where('change_requests.user_id !=', $user->id)
            ->where('change_requests.marga_id', (int) $user->marga_id)
            ->where($this->sqlKerabatDekat((int) $user->person_id), null, false)
            ->orderBy('change_requests.id', 'DESC')
            ->findAll(50);

        return $rows;
    }

    /**
     * @return array{benar: int, salah: int, daftar: list<array<string, mixed>>}
     */
    public function kesaksian(int $id): array
    {
        $daftar = Database::connect()->table('konfirmasi_keluarga k')
            ->select('k.*, u.username, p.nama_lengkap, p.kode_anggota, p.generasi_ke')
            ->join('users u', 'u.id = k.user_id')
            ->join('persons p', 'p.id = k.person_id', 'left')
            ->where('k.change_request_id', $id)
            ->orderBy('k.id')
            ->get()->getResultArray();

        $benar = count(array_filter($daftar, static fn (array $k): bool => (int) $k['benar'] === 1));

        return ['benar' => $benar, 'salah' => count($daftar) - $benar, 'daftar' => $daftar];
    }

    /**
     * Menjalankan usulan. Mengembalikan ID orang yang dibuat/diubah.
     */
    public function setujui(int $id, User $verifikator, ?string $catatan = null): int
    {
        $usulan  = $this->ambilPending($id, $verifikator);
        $data    = $this->bukaPayload($usulan['payload']['data'] ?? []);
        $persons = $this->persons->untukUsulanTerverifikasi();
        $db      = Database::connect();

        $db->transException(true)->transStart();

        try {
            $hasil = match ($usulan['jenis']) {
                'daftar_anggota'  => $this->jalankanPendaftaran($usulan, $data, $persons, $verifikator),
                'tambah_anak'     => $persons->tambahAnak((int) $usulan['person_id'], $data, $verifikator)->id,
                'tambah_pasangan' => $persons->tambahPasangan((int) $usulan['person_id'], $data, $usulan['payload']['pernikahan'] ?? [], $verifikator)->id,
                'ubah_data'       => $persons->ubahProfil((int) $usulan['person_id'], $data, $verifikator)->id,
                'klaim_profil'    => $this->tautkanAkun((int) $usulan['user_id'], (int) $usulan['person_id'], $verifikator),
            };

            $this->model->update($id, [
                'status'              => 'disetujui',
                'catatan_verifikator' => $catatan,
                'hasil_person_id'     => $hasil,
                'reviewed_by'         => $verifikator->id,
                'reviewed_at'         => Time::now()->toDateTimeString(),
            ]);

            $db->transComplete();
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        return $hasil;
    }

    public function tolak(int $id, User $verifikator, string $catatan): void
    {
        $this->ambilPending($id, $verifikator);

        if (trim($catatan) === '') {
            throw new SilsilahException('Alasan penolakan wajib diisi.');
        }

        $this->model->update($id, [
            'status'              => 'ditolak',
            'catatan_verifikator' => $catatan,
            'reviewed_by'         => $verifikator->id,
            'reviewed_at'         => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * Data usulan untuk ditampilkan ke verifikator (NIK/No. KK disamarkan).
     *
     * @return array<string, mixed>
     */
    public function dataTampil(array $usulan): array
    {
        $data = $this->bukaPayload($usulan['payload']['data'] ?? []);
        foreach (self::FIELD_RAHASIA as $f) {
            if (isset($data[$f])) {
                $data[$f] = DataPribadiCipher::samarkan((string) $data[$f]);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $usulan
     */
    public function bolehVerifikasi(User $user, array $usulan): bool
    {
        if ($user->inGroup('superadmin')) {
            return true;
        }
        if (! $user->can('silsilah.verify') || (int) $user->marga_id !== (int) $usulan['marga_id']) {
            return false;
        }
        if (! $this->lingkup->terbatas($user)) {
            return true;
        }

        $person = $usulan['person_id'] ? (new PersonModel())->find((int) $usulan['person_id']) : null;

        return $person !== null && $this->lingkup->mencakupPerson($user, $person, $usulan['kabupaten_kode'] ?? null);
    }

    public function pastikanBisaDiklaim(User $user, Person $person): void
    {
        if ($user->person_id !== null) {
            throw new SilsilahException('Akun Anda sudah tertaut ke data silsilah.');
        }
        if (! $person->isAnggotaGarisMarga() && $person->garis !== 'anak_boru') {
            throw new SilsilahException('Hanya anggota dalam silsilah marga yang dapat diklaim.');
        }
        if (! $person->isHidup()) {
            throw new SilsilahException('Data ini tercatat sudah meninggal.');
        }
        if ((new UserModel())->where('person_id', $person->id)->countAllResults() > 0) {
            throw new SilsilahException('Data ini sudah tertaut ke akun lain.');
        }
    }

    /**
     * @param list<array<string, mixed>> $antara
     */
    public function periksaSilsilahPendaftaran(Person $leluhur, array $antara): void
    {
        if (! $leluhur->bisaPunyaAnak()) {
            throw new SilsilahException('Leluhur yang dipilih harus anggota garis utama atau boru.');
        }
        if ($leluhur->garis === 'boru' && $antara !== []) {
            throw new SilsilahException('Bila induk Anda boru (ibu), pilih ibu Anda langsung tanpa generasi antara.');
        }
        if (count($antara) > self::MAKS_ANTARA) {
            throw new SilsilahException('Terlalu banyak generasi yang belum tercatat. Pilih leluhur yang lebih dekat, atau hubungi Admin Wilayah.');
        }

        $batas = $this->policy->batasSilsilahPokok($leluhur->marga_id);
        if ($leluhur->generasi_ke + 1 <= $batas) {
            throw new SilsilahException(
                "Generasi 1–{$batas} (Silsilah Pokok) hanya diisi oleh Ketua Adat. Pilih leluhur terdekat Anda mulai Generasi {$batas}, "
                . 'atau hubungi Ketua Adat bila leluhur Anda belum tercatat.',
            );
        }
    }

    private function pastikanTidakAdaPendaftaranPending(User $user): void
    {
        $ada = $this->model->where('user_id', $user->id)
            ->whereIn('jenis', ['daftar_anggota', 'klaim_profil'])
            ->where('status', 'pending')
            ->countAllResults();
        if ($ada > 0) {
            throw new SilsilahException('Pendaftaran Anda masih menunggu validasi.');
        }
    }

    /**
     * @param array<string, mixed> $usulan
     * @param array<string, mixed> $dataDiri
     */
    private function jalankanPendaftaran(array $usulan, array $dataDiri, PersonService $persons, User $verifikator): int
    {
        $leluhur = $persons->ambil((int) $usulan['person_id']);
        $antara  = $usulan['payload']['antara'] ?? [];
        $this->periksaSilsilahPendaftaran($leluhur, $antara);

        $induk = $leluhur;
        foreach ($antara as $a) {
            $induk = $persons->tambahAnak($induk->id, [
                'nama_lengkap'  => $a['nama_lengkap'],
                'jenis_kelamin' => 'L',
                'tahun_lahir'   => $a['tahun_lahir'] ?? null,
                'status_hidup'  => $a['status_hidup'] ?? 'tidak_diketahui',
            ], $verifikator);
        }

        $diri = $persons->tambahAnak($induk->id, $dataDiri, $verifikator);

        return $this->tautkanAkun((int) $usulan['user_id'], $diri->id, $verifikator);
    }

    /**
     * Menautkan akun ke data silsilah dan menaikkan calon menjadi member.
     */
    private function tautkanAkun(int $userId, int $personId, User $verifikator): int
    {
        $users  = new UserModel();
        $user   = $users->findById($userId);
        $person = $this->persons->ambil($personId);

        if ($user === null) {
            throw new SilsilahException('Akun pengusul tidak ditemukan.');
        }
        $this->pastikanBisaDiklaim($user, $person);

        $users->update($user->id, ['person_id' => $person->id, 'marga_id' => $person->marga_id]);
        if ($user->inGroup('calon')) {
            $user->removeGroup('calon');
            $user->addGroup('member');
        }
        $this->audit->catat('tautkan_akun', 'users', $user->id, ['person_id' => null], ['person_id' => $person->id], $verifikator->id);

        return $person->id;
    }

    /**
     * @param array<string, mixed> $usulan
     */
    private function bolehBersaksi(User $user, array $usulan): bool
    {
        if ($user->person_id === null || ! $user->can('silsilah.view') || (int) $user->marga_id !== (int) $usulan['marga_id'] || ! $usulan['person_id']) {
            return false;
        }

        return Database::connect()->table('change_requests')
            ->where('id', (int) $usulan['id'])
            ->where($this->sqlKerabatDekat((int) $user->person_id), null, false)
            ->countAllResults() > 0;
    }

    /**
     * Syarat SQL: saksi dan leluhur yang dipilih pengusul bertemu pada leluhur bersama
     * paling jauh JARAK_SAKSI sundut di atas leluhur pilihan (keluarga dekat satu pomparan).
     */
    private function sqlKerabatDekat(int $saksiPersonId): string
    {
        return 'EXISTS (SELECT 1 FROM person_paths up
                  JOIN person_paths sk ON sk.ancestor_id = up.ancestor_id AND sk.descendant_id = ' . $saksiPersonId . '
                 WHERE up.descendant_id = change_requests.person_id AND up.depth <= ' . self::JARAK_SAKSI . ')';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function simpanUsulan(User $user, string $jenis, Person $person, array $payload, ?string $catatan, ?string $kabupaten): int
    {
        return (int) $this->model->insert([
            'marga_id'         => $person->marga_id,
            'user_id'          => $user->id,
            'person_id'        => $person->id,
            'kabupaten_kode'   => $kabupaten,
            'jenis'            => $jenis,
            'payload'          => $payload,
            'status'           => 'pending',
            'catatan_pengusul' => $catatan,
        ]);
    }

    private function domisiliPengusul(User $user): ?string
    {
        if ($user->person_id === null) {
            return null;
        }

        return (new PersonModel())->find((int) $user->person_id)?->kabupaten_kode;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function kabupatenDari(array $data): ?string
    {
        foreach (['desa_kode', 'kecamatan_kode', 'kabupaten_kode'] as $k) {
            if (! empty($data[$k])) {
                return implode('.', array_slice(explode('.', (string) $data[$k]), 0, 2));
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function kunciPayload(array $payload): array
    {
        foreach (self::FIELD_RAHASIA as $f) {
            if (isset($payload['data'][$f]) && $payload['data'][$f] !== '') {
                $payload['data'][$f] = ['enc' => $this->cipher->enkripsi((string) $payload['data'][$f])];
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function ambilPending(int $id, User $verifikator): array
    {
        $usulan = $this->model->find($id);
        if ($usulan === null) {
            throw new SilsilahException('Usulan tidak ditemukan.');
        }
        if (! $this->bolehVerifikasi($verifikator, $usulan)) {
            throw new AksesDitolakException('Anda tidak berhak memverifikasi usulan ini (di luar marga atau lingkup wilayah/cabang Anda).');
        }
        if ($usulan['status'] !== 'pending') {
            throw new SilsilahException('Usulan ini sudah diproses.');
        }

        return $usulan;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function bukaPayload(array $data): array
    {
        foreach (self::FIELD_RAHASIA as $f) {
            if (isset($data[$f]['enc'])) {
                $data[$f] = $this->cipher->dekripsi($data[$f]['enc']);
            }
        }

        return $data;
    }
}
