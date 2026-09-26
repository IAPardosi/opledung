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
 * Validasi dua lapis:
 *  1. Validasi keluarga: pengusul menunjuk member sah dalam garis langsungnya, paling jauh
 *     2 sundut ke atas (ayah/ompung) atau ke bawah (anak/pahompu), yang menyatakan benar/salah.
 *     Member yang mengusulkan keluarganya sendiri (anak/pasangan) sudah dihitung sebagai validator.
 *  2. Pengesahan oleh Penatua Punguan (sesuai punguan pengusul), Admin Marga, atau Ketua Adat
 *     untuk Silsilah Pokok (G1–batas).
 * Kesaksian kerabat lain tetap boleh diberikan sebagai pelengkap.
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

    /**
     * Jarak sundut maksimal validator keluarga dari orang yang diusulkan (ke atas atau ke bawah).
     */
    public const JARAK_VALIDATOR = 2;

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
    public function ajukan(User $user, string $jenis, Person $person, array $payload, ?string $catatan = null, ?int $validatorUserId = null): int
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

        $keluarga = $this->tentukanValidasiKeluarga($user, $this->kandidatValidator($jenis, $person), $validatorUserId);

        return $this->simpanUsulan($user, $jenis, $person, $this->kunciPayload($payload), $catatan, $this->domisiliPengusul($user), $user->punguan_id !== null ? (int) $user->punguan_id : null, $keluarga);
    }

    /**
     * Pendaftaran anggota baru beserta silsilahnya.
     *
     * @param list<array<string, mixed>> $antara   generasi antara, dari anak leluhur sampai ayah pendaftar
     * @param array<string, mixed>       $dataDiri data pribadi pendaftar (kepala keluarga)
     * @param array<string, mixed>       $keluarga ['istri' => [...]|null, 'anak' => list<[...]>]
     */
    public function ajukanPendaftaran(
        User $user,
        Person $leluhur,
        array $antara,
        array $dataDiri,
        ?string $catatan = null,
        array $keluarga = [],
        ?int $punguanId = null,
        ?int $validatorUserId = null,
    ): int {
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

        $istri = $this->bersihkanIstri($keluarga['istri'] ?? null);
        $anak  = $this->bersihkanAnak($keluarga['anak'] ?? []);
        if ($istri !== null && empty($istri['marga_nama'])) {
            throw new ValidasiDataException(['istri' => 'Marga istri/suami wajib diisi.']);
        }

        $validasi = $this->tentukanValidasiKeluarga($user, $this->kandidatValidator('daftar_anggota', $leluhur, count($antara)), $validatorUserId);

        return $this->simpanUsulan(
            $user,
            'daftar_anggota',
            $leluhur,
            $this->kunciPayload(['antara' => $antara, 'data' => $dataDiri, 'istri' => $istri, 'anak' => $anak]),
            $catatan,
            $kabupaten,
            $punguanId,
            $validasi,
        );
    }

    /**
     * Member sah yang boleh menjadi validator keluarga: garis langsung, paling jauh 2 sundut
     * ke atas atau ke bawah dari orang yang diusulkan.
     *
     * - daftar_anggota: $acuan = leluhur terdekat; orang baru berada $antara + 1 sundut di bawahnya.
     * - tambah_anak: orang baru adalah anak $acuan.
     * - lainnya (klaim, ubah, pasangan): orangnya adalah $acuan sendiri.
     *
     * @return list<array{user_id: int, username: string, person_id: int, nama_lengkap: string, generasi_ke: int, hubungan: string}>
     */
    public function kandidatValidator(string $jenis, Person $acuan, int $antara = 0): array
    {
        $db    = Database::connect();
        $turun = match ($jenis) {
            'daftar_anggota' => $antara + 1,
            'tambah_anak'    => 1,
            default          => 0,
        };

        // Leluhur orang itu: $acuan dan leluhurnya, selama jaraknya ke orang itu ≤ JARAK_VALIDATOR.
        $atas = $turun <= self::JARAK_VALIDATOR
            ? $db->table('person_paths')->select('ancestor_id AS id, depth + ' . $turun . ' AS jarak', false)
                ->where('descendant_id', $acuan->id)->where('depth <=', self::JARAK_VALIDATOR - $turun)
                ->where('depth + ' . $turun . ' >', 0, false)
                ->get()->getResultArray()
            : [];
        // Keturunan orang itu (hanya bila orangnya sudah ada di silsilah).
        $bawah = $turun === 0
            ? $db->table('person_paths')->select('descendant_id AS id, -depth AS jarak', false)
                ->where('ancestor_id', $acuan->id)->where('depth >', 0)->where('depth <=', self::JARAK_VALIDATOR)
                ->get()->getResultArray()
            : [];

        $jarak = [];
        foreach ([...$atas, ...$bawah] as $r) {
            $jarak[(int) $r['id']] = (int) $r['jarak'];
        }
        if ($jarak === []) {
            return [];
        }

        $rows = $db->table('users u')
            ->select('u.id AS user_id, u.username, p.id AS person_id, p.nama_lengkap, p.generasi_ke, p.jenis_kelamin')
            ->join('persons p', 'p.id = u.person_id AND p.deleted_at IS NULL')
            ->whereIn('u.person_id', array_keys($jarak))
            ->where('u.active', 1)
            ->where('u.deleted_at', null)
            ->where('NOT EXISTS (SELECT 1 FROM auth_groups_users g WHERE g.user_id = u.id AND g.`group` = \'calon\')', null, false)
            ->get()->getResultArray();

        $label = static fn (int $j, string $jk): string => match ($j) {
            1       => $jk === 'L' ? 'Ayah' : 'Ibu',
            2       => 'Ompung',
            -1      => $jk === 'L' ? 'Anak' : 'Boru',
            -2      => 'Pahompu',
            default => 'Keluarga',
        };

        $hasil = [];
        foreach ($rows as $r) {
            $hasil[] = [
                'user_id'      => (int) $r['user_id'],
                'username'     => $r['username'],
                'person_id'    => (int) $r['person_id'],
                'nama_lengkap' => $r['nama_lengkap'],
                'generasi_ke'  => (int) $r['generasi_ke'],
                'hubungan'     => $label($jarak[(int) $r['person_id']], $r['jenis_kelamin']),
            ];
        }

        return $hasil;
    }

    /**
     * Keputusan validator keluarga atas usulan yang menunjuknya.
     */
    public function validasiKeluarga(int $id, User $user, bool $benar, ?string $catatan = null): void
    {
        $usulan = $this->model->find($id);
        if ($usulan === null || $usulan['status'] !== 'pending') {
            throw new SilsilahException('Usulan tidak ditemukan atau sudah diproses.');
        }
        if ((int) $usulan['validator_user_id'] !== $user->id) {
            throw new AksesDitolakException('Anda bukan validator keluarga untuk usulan ini.');
        }
        if (! $benar && trim((string) $catatan) === '') {
            throw new SilsilahException('Tuliskan alasan bila data tidak benar, agar pengusul dapat memperbaikinya.');
        }

        $this->model->update($id, [
            'status_keluarga'  => $benar ? 'benar' : 'salah',
            'catatan_keluarga' => $catatan !== null ? mb_substr(trim($catatan), 0, 1000) : null,
            'keluarga_at'      => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * Penatua/admin melewati validasi keluarga (mis. validator tidak aktif), wajib dengan alasan.
     */
    public function lewatiValidasiKeluarga(int $id, User $admin, string $alasan): void
    {
        $usulan = $this->ambilPending($id, $admin);
        if (trim($alasan) === '') {
            throw new SilsilahException('Alasan wajib diisi untuk melewati validasi keluarga.');
        }
        if ($usulan['status_keluarga'] !== 'menunggu') {
            throw new SilsilahException('Validasi keluarga untuk usulan ini tidak sedang menunggu.');
        }

        $this->model->update($id, [
            'status_keluarga'  => 'tidak_ada',
            'catatan_keluarga' => 'Dilewati oleh ' . $admin->username . ': ' . mb_substr(trim($alasan), 0, 900),
            'keluarga_at'      => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * Usulan yang menunggu keputusan pengguna ini sebagai validator keluarga.
     *
     * @return list<array<string, mixed>>
     */
    public function tugasValidasi(User $user): array
    {
        return $this->model
            ->select('change_requests.*, persons.nama_lengkap AS nama_acuan, persons.generasi_ke AS generasi_acuan, users.username')
            ->join('persons', 'persons.id = change_requests.person_id', 'left')
            ->join('users', 'users.id = change_requests.user_id')
            ->where('change_requests.status', 'pending')
            ->where('change_requests.validator_user_id', $user->id)
            ->where('change_requests.status_keluarga', 'menunggu')
            ->orderBy('change_requests.id', 'ASC')
            ->findAll(50);
    }

    /**
     * Jumlah hal yang menunggu pengguna: validasi keluarga + kesaksian yang belum diberikan.
     */
    public function jumlahTugasKeluarga(User $user): int
    {
        $validasi = $this->model->where('status', 'pending')->where('validator_user_id', $user->id)
            ->where('status_keluarga', 'menunggu')->countAllResults();

        return $validasi + count(array_filter($this->menungguKesaksian($user), static fn ($r) => $r['kesaksian_saya'] === null));
    }

    /**
     * @param list<array<string, mixed>> $kandidat
     *
     * @return array{validator_user_id: ?int, status_keluarga: string, catatan_keluarga?: string, keluarga_at?: string}
     */
    private function tentukanValidasiKeluarga(User $pengusul, array $kandidat, ?int $validatorUserId): array
    {
        $ids = array_column($kandidat, 'user_id');

        // Pengusul sendiri berada dalam garis langsung (mis. ayah mengusulkan anaknya): sudah tervalidasi keluarga.
        if (in_array($pengusul->id, $ids, true)) {
            return [
                'validator_user_id' => $pengusul->id,
                'status_keluarga'   => 'benar',
                'catatan_keluarga'  => 'Diajukan sendiri oleh keluarga dalam garis langsung.',
                'keluarga_at'       => Time::now()->toDateTimeString(),
            ];
        }
        if ($kandidat === []) {
            return ['validator_user_id' => null, 'status_keluarga' => 'tidak_ada'];
        }
        if ($validatorUserId === null || ! in_array($validatorUserId, $ids, true)) {
            throw new ValidasiDataException(['validator' => 'Pilih anggota keluarga (orang tua/ompung atau anak/pahompu) yang akan memvalidasi.']);
        }

        return ['validator_user_id' => $validatorUserId, 'status_keluarga' => 'menunggu'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function bersihkanIstri(?array $istri): ?array
    {
        if ($istri === null || trim((string) ($istri['nama_lengkap'] ?? '')) === '') {
            return null;
        }

        return array_filter([
            'nama_lengkap' => mb_substr(trim((string) $istri['nama_lengkap']), 0, 150),
            'marga_nama'   => mb_substr(trim((string) ($istri['marga_nama'] ?? '')), 0, 100),
            'tahun_lahir'  => is_numeric($istri['tahun_lahir'] ?? null) ? (int) $istri['tahun_lahir'] : null,
            'status_hidup' => in_array($istri['status_hidup'] ?? null, ['hidup', 'meninggal'], true) ? $istri['status_hidup'] : 'hidup',
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param list<array<string, mixed>> $anak
     *
     * @return list<array<string, mixed>>
     */
    private function bersihkanAnak(array $anak): array
    {
        $hasil = [];
        foreach ($anak as $a) {
            if (trim((string) ($a['nama_lengkap'] ?? '')) === '' || ! in_array($a['jenis_kelamin'] ?? null, ['L', 'P'], true)) {
                continue;
            }
            $hasil[] = array_filter([
                'nama_lengkap'  => mb_substr(trim((string) $a['nama_lengkap']), 0, 150),
                'jenis_kelamin' => $a['jenis_kelamin'],
                'tahun_lahir'   => is_numeric($a['tahun_lahir'] ?? null) ? (int) $a['tahun_lahir'] : null,
                'status_hidup'  => 'hidup',
            ], static fn ($v) => $v !== null);
        }

        return array_slice($hasil, 0, 20);
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
        if ($usulan['status_keluarga'] === 'menunggu') {
            throw new SilsilahException('Masih menunggu validasi keluarga. Bila validator tidak dapat dihubungi, lewati validasi keluarga dengan alasan.');
        }
        if ($usulan['status_keluarga'] === 'salah') {
            throw new SilsilahException('Validator keluarga menyatakan data ini tidak benar. Tolak usulan agar pengusul memperbaikinya.');
        }
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
                'klaim_profil'    => $this->tautkanAkun((int) $usulan['user_id'], (int) $usulan['person_id'], $verifikator, $usulan['punguan_id'] !== null ? (int) $usulan['punguan_id'] : null),
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

        return $this->lingkup->mencakupUsulan($user, $usulan, $person);
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

        // Unit keluarga kepala keluarga: pasangan dan anak-anak.
        $pasanganId = null;
        if (! empty($usulan['payload']['istri'])) {
            $pasanganId = $persons->tambahPasangan($diri->id, $usulan['payload']['istri'], [], $verifikator)->id;
        }
        foreach ($usulan['payload']['anak'] ?? [] as $a) {
            $persons->tambahAnak($diri->id, [...$a, 'pasangan_id' => $pasanganId], $verifikator);
        }

        return $this->tautkanAkun((int) $usulan['user_id'], $diri->id, $verifikator, $usulan['punguan_id'] !== null ? (int) $usulan['punguan_id'] : null);
    }

    /**
     * Menautkan akun ke data silsilah dan menaikkan calon menjadi member.
     */
    private function tautkanAkun(int $userId, int $personId, User $verifikator, ?int $punguanId = null): int
    {
        $users  = new UserModel();
        $user   = $users->findById($userId);
        $person = $this->persons->ambil($personId);

        if ($user === null) {
            throw new SilsilahException('Akun pengusul tidak ditemukan.');
        }
        $this->pastikanBisaDiklaim($user, $person);

        $users->update($user->id, array_filter([
            'person_id'  => $person->id,
            'marga_id'   => $person->marga_id,
            'punguan_id' => $punguanId ?? $user->punguan_id,
        ], static fn ($v) => $v !== null));
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
    private function simpanUsulan(
        User $user,
        string $jenis,
        Person $person,
        array $payload,
        ?string $catatan,
        ?string $kabupaten,
        ?int $punguanId = null,
        array $keluarga = [],
    ): int {
        return (int) $this->model->insert([
            ...$keluarga,
            'punguan_id'       => $punguanId,
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
