<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Models\MarriageModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use Config\Database;

/**
 * Lingkup kerja Admin Wilayah (tabel admin_lingkup):
 *  - 'cabang' : ID leluhur; admin menangani seluruh pomparan (keturunan) leluhur itu
 *  - 'wilayah': kode provinsi/kab/kota domisili; admin menangani anggota yang tinggal di sana
 *
 * Role lain (Super Admin, Ketua Adat, Admin Marga) tidak dibatasi lingkup ini.
 */
class LingkupAdmin
{
    /**
     * @var array<int, list<array{jenis: string, nilai: string}>>
     */
    private array $cache = [];

    private readonly BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Admin wilayah yang tidak punya role lebih luas.
     */
    public function terbatas(User $user): bool
    {
        return $user->inGroup('admin_wilayah') && ! $user->inGroup('superadmin', 'ketua_adat', 'verifikator');
    }

    /**
     * @return list<array{jenis: string, nilai: string}>
     */
    public function daftar(int $userId): array
    {
        return $this->cache[$userId] ??= $this->db->table('admin_lingkup')
            ->select('jenis, nilai')
            ->where('user_id', $userId)
            ->get()->getResultArray();
    }

    /**
     * @param list<array{jenis: string, nilai: string}> $lingkup
     */
    public function simpan(int $userId, array $lingkup): void
    {
        $this->db->table('admin_lingkup')->where('user_id', $userId)->delete();
        foreach ($lingkup as $l) {
            $this->db->table('admin_lingkup')->insert([...$l, 'user_id' => $userId, 'created_at' => date('Y-m-d H:i:s')]);
        }
        unset($this->cache[$userId]);
    }

    /**
     * Apakah orang ini (atau pasangannya, bila ia pasangan) berada dalam lingkup admin?
     */
    public function mencakupPerson(User $user, Person $person, ?string $kabupatenTambahan = null): bool
    {
        if (! $this->terbatas($user)) {
            return true;
        }

        $ids = [$person->id];
        if ($person->garis === 'pasangan') {
            $ids = [...$ids, ...(new MarriageModel())->pasanganIds($person->id)];
        }

        foreach ($this->daftar($user->id) as $l) {
            if ($l['jenis'] === 'cabang') {
                $ada = $this->db->table('person_paths')
                    ->where('ancestor_id', (int) $l['nilai'])
                    ->whereIn('descendant_id', $ids)
                    ->countAllResults();
                if ($ada > 0) {
                    return true;
                }
            }
            if ($l['jenis'] === 'wilayah') {
                foreach (array_filter([$person->kabupaten_kode, $kabupatenTambahan]) as $kab) {
                    if ($this->cocokWilayah($l['nilai'], $kab)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Membatasi query change_requests hanya pada usulan dalam lingkup admin.
     */
    public function saringUsulan(BaseBuilder $builder, User $user): void
    {
        if (! $this->terbatas($user)) {
            return;
        }

        $syarat = [];
        foreach ($this->daftar($user->id) as $l) {
            if ($l['jenis'] === 'cabang') {
                $syarat[] = 'EXISTS (SELECT 1 FROM person_paths lp WHERE lp.ancestor_id = ' . (int) $l['nilai']
                    . ' AND lp.descendant_id = change_requests.person_id)';
            } else {
                $kode     = $this->db->escapeLikeString($l['nilai']);
                $syarat[] = "(change_requests.kabupaten_kode = {$this->db->escape($l['nilai'])} OR change_requests.kabupaten_kode LIKE '{$kode}.%')";
            }
        }

        $builder->where($syarat === [] ? '1 = 0' : '(' . implode(' OR ', $syarat) . ')', null, false);
    }

    /**
     * '12' mencakup '12.02'; '12.02' mencakup '12.02' dan '12.02.01'.
     */
    private function cocokWilayah(string $lingkup, string $kode): bool
    {
        return $kode === $lingkup || str_starts_with($kode, $lingkup . '.');
    }
}
