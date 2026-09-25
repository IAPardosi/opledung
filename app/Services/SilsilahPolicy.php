<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Exceptions\AksesDitolakException;
use App\Models\MargaModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Aturan hak akses data silsilah (docs/STANDAR.md bagian 4 dan 5).
 *
 * Aktor null berarti proses sistem (seeder/CLI) dan selalu diizinkan.
 * Controller wajib selalu mengirim pengguna yang sedang login.
 */
class SilsilahPolicy
{
    /**
     * @var array<int, int> cache batas Silsilah Pokok per marga
     */
    private array $batasPokok = [];

    public function __construct(private readonly MargaModel $margaModel = new MargaModel())
    {
    }

    public function batasSilsilahPokok(int $margaId): int
    {
        if (! isset($this->batasPokok[$margaId])) {
            $marga = $this->margaModel->find($margaId);
            $this->batasPokok[$margaId] = (int) ($marga['batas_silsilah_pokok'] ?? 10);
        }

        return $this->batasPokok[$margaId];
    }

    public function isSilsilahPokok(int $margaId, int $generasi): bool
    {
        return $generasi <= $this->batasSilsilahPokok($margaId);
    }

    /**
     * Boleh menambah/mengubah struktur silsilah secara langsung pada generasi tertentu?
     */
    public function bolehKelolaGenerasi(?User $user, int $margaId, int $generasi): bool
    {
        if ($user === null || $this->isSuperAdmin($user)) {
            return true;
        }

        if (! $this->margaSama($user, $margaId)) {
            return false;
        }

        if ($this->isSilsilahPokok($margaId, $generasi)) {
            return $user->can('silsilah.pokok');
        }

        return $user->can('silsilah.edit');
    }

    /**
     * Boleh mengubah data profil seseorang?
     * Member boleh mengubah profilnya sendiri selama belum dikunci Ketua Adat.
     */
    public function bolehUbahProfil(?User $user, Person $person): bool
    {
        if ($user === null || $this->isSuperAdmin($user)) {
            return true;
        }

        if (! $this->margaSama($user, $person->marga_id)) {
            return false;
        }

        if ($person->isTerkunci()) {
            return $user->can('silsilah.pokok');
        }

        if ($this->bolehKelolaGenerasi($user, $person->marga_id, $person->generasi_ke)) {
            return true;
        }

        return $user->person_id !== null && (int) $user->person_id === $person->id;
    }

    public function bolehValidasi(?User $user, Person $person): bool
    {
        if ($user === null || $this->isSuperAdmin($user)) {
            return true;
        }

        if (! $this->margaSama($user, $person->marga_id)) {
            return false;
        }

        return $this->isSilsilahPokok($person->marga_id, $person->generasi_ke)
            ? $user->can('silsilah.pokok')
            : $user->can('silsilah.verify');
    }

    public function bolehLihatDataSensitif(?User $user, Person $person): bool
    {
        if ($user === null || $this->isSuperAdmin($user)) {
            return true;
        }

        return $this->margaSama($user, $person->marga_id) && $user->can('data.sensitive');
    }

    /**
     * @throws AksesDitolakException
     */
    public function pastikan(bool $boleh, string $pesan = 'Anda tidak memiliki hak untuk melakukan aksi ini.'): void
    {
        if (! $boleh) {
            throw new AksesDitolakException($pesan);
        }
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->inGroup('superadmin');
    }

    private function margaSama(User $user, int $margaId): bool
    {
        return $user->marga_id !== null && (int) $user->marga_id === $margaId;
    }
}
