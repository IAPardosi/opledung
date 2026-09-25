<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Exceptions\AksesDitolakException;
use App\Exceptions\SilsilahException;
use App\Models\ChangeRequestModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Config\Database;
use Throwable;

/**
 * Usulan data dari member (pending → disetujui/ditolak), docs/STANDAR.md 4.1.
 *
 * Persetujuan dijalankan lewat PersonService dengan verifikator sebagai aktor,
 * sehingga aturan Silsilah Pokok tetap berlaku saat usulan disetujui.
 */
class UsulanService
{
    public const JENIS = [
        'tambah_anak'     => 'Tambah anak',
        'tambah_pasangan' => 'Tambah pasangan',
        'ubah_data'       => 'Ubah data',
        'klaim_profil'    => 'Klaim profil ("Ini saya")',
    ];

    /**
     * Field payload yang disimpan terenkripsi selama usulan menunggu.
     */
    private const FIELD_RAHASIA = ['nik', 'no_kk'];

    public function __construct(
        private readonly ChangeRequestModel $model = new ChangeRequestModel(),
        private readonly PersonService $persons = new PersonService(),
        private readonly SilsilahPolicy $policy = new SilsilahPolicy(),
        private readonly DataPribadiCipher $cipher = new DataPribadiCipher(),
        private readonly AuditLogger $audit = new AuditLogger(),
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function ajukan(User $user, string $jenis, Person $person, array $payload, ?string $catatan = null): int
    {
        if (! isset(self::JENIS[$jenis])) {
            throw new SilsilahException('Jenis usulan tidak dikenal.');
        }
        if (! $user->can('silsilah.propose') && ! $user->inGroup('superadmin')) {
            throw new AksesDitolakException('Anda tidak memiliki hak untuk mengajukan usulan.');
        }
        if ($user->marga_id === null || (int) $user->marga_id !== $person->marga_id) {
            throw new AksesDitolakException('Usulan hanya dapat diajukan untuk marga Anda sendiri.');
        }

        if ($jenis === 'klaim_profil') {
            $this->pastikanBisaDiklaim($user, $person);
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

        foreach (self::FIELD_RAHASIA as $f) {
            if (isset($payload['data'][$f]) && $payload['data'][$f] !== '') {
                $payload['data'][$f] = ['enc' => $this->cipher->enkripsi((string) $payload['data'][$f])];
            }
        }

        return (int) $this->model->insert([
            'marga_id'         => $person->marga_id,
            'user_id'          => $user->id,
            'person_id'        => $person->id,
            'jenis'            => $jenis,
            'payload'          => $payload,
            'status'           => 'pending',
            'catatan_pengusul' => $catatan,
        ]);
    }

    /**
     * Menjalankan usulan. Mengembalikan ID orang yang dibuat/diubah.
     */
    public function setujui(int $id, User $verifikator, ?string $catatan = null): int
    {
        $usulan = $this->ambilPending($id, $verifikator);
        $data   = $this->bukaPayload($usulan['payload']['data'] ?? []);
        $db     = Database::connect();

        $db->transException(true)->transStart();

        try {
            $hasil = match ($usulan['jenis']) {
                'tambah_anak'     => $this->persons->tambahAnak((int) $usulan['person_id'], $data, $verifikator)->id,
                'tambah_pasangan' => $this->persons->tambahPasangan((int) $usulan['person_id'], $data, $usulan['payload']['pernikahan'] ?? [], $verifikator)->id,
                'ubah_data'       => $this->persons->ubahProfil((int) $usulan['person_id'], $data, $verifikator)->id,
                'klaim_profil'    => $this->tautkanAkun($usulan, $verifikator),
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

    public function bolehVerifikasi(User $user, int $margaId): bool
    {
        if ($user->inGroup('superadmin')) {
            return true;
        }

        return $user->can('silsilah.verify') && (int) $user->marga_id === $margaId;
    }

    public function pastikanBisaDiklaim(User $user, Person $person): void
    {
        if ($user->person_id !== null) {
            throw new SilsilahException('Akun Anda sudah tertaut ke data silsilah.');
        }
        if (! $person->isAnggotaGarisMarga() || ! $person->isHidup()) {
            throw new SilsilahException('Hanya anggota garis marga yang masih hidup yang dapat diklaim.');
        }
        if ((new UserModel())->where('person_id', $person->id)->countAllResults() > 0) {
            throw new SilsilahException('Data ini sudah tertaut ke akun lain.');
        }
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
        if (! $this->bolehVerifikasi($verifikator, (int) $usulan['marga_id'])) {
            throw new AksesDitolakException('Anda tidak berhak memverifikasi usulan ini.');
        }
        if ($usulan['status'] !== 'pending') {
            throw new SilsilahException('Usulan ini sudah diproses.');
        }

        return $usulan;
    }

    /**
     * @param array<string, mixed> $usulan
     */
    private function tautkanAkun(array $usulan, User $verifikator): int
    {
        $users  = new UserModel();
        $user   = $users->findById((int) $usulan['user_id']);
        $person = $this->persons->ambil((int) $usulan['person_id']);

        if ($user === null) {
            throw new SilsilahException('Akun pengusul tidak ditemukan.');
        }
        $this->pastikanBisaDiklaim($user, $person);

        $users->update($user->id, ['person_id' => $person->id]);
        $this->audit->catat('klaim_profil', 'users', $user->id, ['person_id' => null], ['person_id' => $person->id], $verifikator->id);

        return $person->id;
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
