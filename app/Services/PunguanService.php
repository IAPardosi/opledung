<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AksesDitolakException;
use App\Exceptions\SilsilahException;
use App\Models\KeanggotaanPunguanModel;
use App\Models\PersonModel;
use App\Models\PunguanModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;

/**
 * Keanggotaan punguan.
 *
 *  - Member Marga   : tercatat di silsilah marga (semua orang di pohon, termasuk yang punya akun web).
 *  - Member Punguan : member marga yang resmi terdaftar dan disahkan Penatua di satu punguan.
 *                     Hanya member punguan aktif yang memiliki kewajiban (iuran, dll.) dan catatan keuangan.
 *
 * Seseorang hanya boleh menjadi member punguan aktif di satu punguan dalam satu waktu.
 */
class PunguanService
{
    public function __construct(
        private readonly KeanggotaanPunguanModel $keanggotaan = new KeanggotaanPunguanModel(),
        private readonly LingkupAdmin $lingkup = new LingkupAdmin(),
        private readonly AuditLogger $audit = new AuditLogger(),
    ) {
    }

    /**
     * ID punguan yang boleh diurus pengguna ini, atau null bila semua (Super Admin).
     *
     * @return list<int>|null
     */
    public function punguanKelolaan(User $user): ?array
    {
        if ($user->inGroup('superadmin')) {
            return null;
        }
        $ids = $this->lingkup->punguanIds($user);
        if ($user->inGroup('humas', 'penatua') && $user->punguan_id !== null) {
            $ids[] = (int) $user->punguan_id;
        }

        return array_values(array_unique($ids));
    }

    public function bolehKelola(User $user, int $punguanId): bool
    {
        $ids = $this->punguanKelolaan($user);

        return $ids === null || in_array($punguanId, $ids, true);
    }

    /**
     * Punguan yang bisa dipilih pengguna ini di halaman pengurus.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarPunguan(User $user, int $margaId): array
    {
        $ids = $this->punguanKelolaan($user);

        return array_values(array_filter(
            (new PunguanModel())->aktif($margaId),
            static fn (array $p): bool => $ids === null || in_array((int) $p['id'], $ids, true),
        ));
    }

    /**
     * Mengajukan seseorang menjadi member punguan.
     *  - Oleh member sendiri (mandiri) atau Humas → status 'menunggu', disahkan Penatua.
     *  - Oleh Penatua/Super Admin punguan itu → langsung 'aktif'.
     */
    public function ajukan(User $aktor, int $punguanId, int $personId, ?string $catatan = null, ?string $tanggalMasuk = null): int
    {
        $punguan = (new PunguanModel())->find($punguanId);
        if ($punguan === null || ! $punguan['is_active']) {
            throw new SilsilahException('Punguan tidak ditemukan atau tidak aktif.');
        }
        $person = (new PersonModel())->find($personId);
        if ($person === null) {
            throw new SilsilahException('Anggota tidak ditemukan di silsilah.');
        }
        if ((int) $person->marga_id !== (int) $punguan['marga_id']) {
            throw new SilsilahException('Anggota ini tidak tercatat di silsilah marga punguan tersebut.');
        }

        $mandiri  = $aktor->person_id !== null && (int) $aktor->person_id === $personId;
        $pengurus = $this->bolehKelola($aktor, $punguanId) && $aktor->can('punguan.anggota');
        if (! $mandiri && ! $pengurus) {
            throw new AksesDitolakException('Anda tidak berhak mendaftarkan anggota di punguan ini.');
        }
        if (! $person->isHidup()) {
            throw new SilsilahException('Anggota yang sudah meninggal tidak dapat didaftarkan sebagai member punguan.');
        }

        $berlaku = $this->keanggotaan->berlaku($personId);
        if ($berlaku !== null) {
            throw new SilsilahException(sprintf(
                '%s sudah %s di %s.',
                $person->nama_lengkap,
                $berlaku['status'] === 'aktif' ? 'menjadi member punguan' : 'diajukan menjadi member punguan',
                $berlaku['nama_punguan'],
            ));
        }

        $langsung = $pengurus && $aktor->can('punguan.sahkan');
        $data     = [
            'punguan_id'    => $punguanId,
            'person_id'     => $personId,
            'status'        => $langsung ? 'aktif' : 'menunggu',
            'tanggal_masuk' => $langsung ? ($tanggalMasuk ?: Time::today()->toDateString()) : $tanggalMasuk,
            'diajukan_oleh' => $aktor->id,
            'disahkan_oleh' => $langsung ? $aktor->id : null,
            'disahkan_at'   => $langsung ? Time::now()->toDateTimeString() : null,
            'catatan'       => $catatan ? trim($catatan) : null,
        ];
        if ($langsung) {
            $data['nomor_anggota'] = $this->nomorBaru($punguanId);
        }

        $id = (int) $this->keanggotaan->insert($data);
        $this->audit->catat('ajukan', 'keanggotaan_punguan', $id, null, $data, $aktor->id);

        return $id;
    }

    /**
     * Penatua mengesahkan atau menolak pengajuan member punguan.
     */
    public function sahkan(int $id, User $aktor, bool $setuju, ?string $catatan = null, ?string $tanggalMasuk = null): void
    {
        $k = $this->ambilUntukPenatua($id, $aktor);
        if ($k['status'] !== 'menunggu') {
            throw new SilsilahException('Pengajuan ini sudah diproses.');
        }
        if (! $setuju && trim((string) $catatan) === '') {
            throw new SilsilahException('Tuliskan alasan penolakan.');
        }

        $data = [
            'status'        => $setuju ? 'aktif' : 'ditolak',
            'disahkan_oleh' => $aktor->id,
            'disahkan_at'   => Time::now()->toDateTimeString(),
            'catatan'       => trim((string) $catatan) ?: $k['catatan'],
        ];
        if ($setuju) {
            $data['tanggal_masuk'] = $tanggalMasuk ?: ($k['tanggal_masuk'] ?: Time::today()->toDateString());
            $data['nomor_anggota'] = $k['nomor_anggota'] ?: $this->nomorBaru((int) $k['punguan_id']);
        }

        $this->keanggotaan->update($id, $data);
        $this->audit->catat($setuju ? 'sahkan' : 'tolak', 'keanggotaan_punguan', $id, ['status' => $k['status']], $data, $aktor->id);
    }

    /**
     * Mengakhiri keanggotaan (pindah, keluar, atau meninggal). Riwayat keuangan tetap tersimpan.
     */
    public function nonaktifkan(int $id, User $aktor, string $alasan, ?string $tanggal = null): void
    {
        $k = $this->ambilUntukPenatua($id, $aktor);
        if ($k['status'] !== 'aktif') {
            throw new SilsilahException('Hanya member punguan aktif yang dapat dinonaktifkan.');
        }
        if (trim($alasan) === '') {
            throw new SilsilahException('Tuliskan alasan (mis. pindah domisili, mengundurkan diri, meninggal).');
        }

        $data = ['status' => 'nonaktif', 'tanggal_keluar' => $tanggal ?: Time::today()->toDateString(), 'catatan' => trim($alasan)];
        $this->keanggotaan->update($id, $data);
        $this->audit->catat('nonaktifkan', 'keanggotaan_punguan', $id, ['status' => 'aktif'], $data, $aktor->id);
    }

    /**
     * Mengaktifkan kembali keanggotaan yang nonaktif.
     */
    public function aktifkanKembali(int $id, User $aktor): void
    {
        $k = $this->ambilUntukPenatua($id, $aktor);
        if ($k['status'] !== 'nonaktif') {
            throw new SilsilahException('Keanggotaan ini tidak berstatus nonaktif.');
        }
        if ($this->keanggotaan->berlaku((int) $k['person_id']) !== null) {
            throw new SilsilahException('Orang ini sudah terdaftar di punguan lain.');
        }

        $data = ['status' => 'aktif', 'tanggal_keluar' => null];
        $this->keanggotaan->update($id, $data);
        $this->audit->catat('aktifkan', 'keanggotaan_punguan', $id, ['status' => 'nonaktif'], $data, $aktor->id);
    }

    /**
     * Mengakhiri keanggotaan punguan orang yang ditandai meninggal (akibat perubahan status oleh admin).
     */
    public function akhiriKarenaMeninggal(int $personId, User $aktor, mixed $tanggalWafat = null): bool
    {
        $k = $this->keanggotaan->berlaku($personId);
        if ($k === null) {
            return false;
        }

        $tanggal = $tanggalWafat instanceof \DateTimeInterface ? $tanggalWafat->format('Y-m-d') : ($tanggalWafat ? substr((string) $tanggalWafat, 0, 10) : Time::today()->toDateString());
        $data    = [
            'status'         => $k['status'] === 'aktif' ? 'nonaktif' : 'ditolak',
            'tanggal_keluar' => $tanggal,
            'catatan'        => 'Meninggal dunia',
        ];
        $this->keanggotaan->update((int) $k['id'], $data);
        $this->audit->catat('nonaktifkan', 'keanggotaan_punguan', (int) $k['id'], ['status' => $k['status']], $data, $aktor->id);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function ambil(int $id): array
    {
        $k = $this->keanggotaan
            ->select('keanggotaan_punguan.*, punguan.nama AS nama_punguan, persons.nama_lengkap, persons.kode_anggota, persons.generasi_ke, persons.status_hidup')
            ->join('punguan', 'punguan.id = keanggotaan_punguan.punguan_id')
            ->join('persons', 'persons.id = keanggotaan_punguan.person_id')
            ->find($id);
        if ($k === null) {
            throw new SilsilahException('Data keanggotaan tidak ditemukan.');
        }

        return $k;
    }

    /**
     * Daftar keanggotaan sebuah punguan.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(int $punguanId, ?string $status = null, string $cari = ''): array
    {
        $b = $this->keanggotaan
            ->select('keanggotaan_punguan.*, persons.nama_lengkap, persons.kode_anggota, persons.generasi_ke, persons.garis, persons.status_hidup,
                users.username AS akun, pengaju.username AS pengaju')
            ->join('persons', 'persons.id = keanggotaan_punguan.person_id')
            ->join('users', 'users.person_id = persons.id', 'left')
            ->join('users pengaju', 'pengaju.id = keanggotaan_punguan.diajukan_oleh', 'left')
            ->where('keanggotaan_punguan.punguan_id', $punguanId);
        if ($status !== null) {
            $b->where('keanggotaan_punguan.status', $status);
        }
        if ($cari !== '') {
            $b->groupStart()->like('persons.nama_lengkap', $cari)->orLike('persons.kode_anggota', $cari)->orLike('keanggotaan_punguan.nomor_anggota', $cari)->groupEnd();
        }

        return $b->orderBy("FIELD(keanggotaan_punguan.status, 'menunggu', 'aktif', 'nonaktif', 'ditolak')", '', false)
            ->orderBy('persons.nama_lengkap')
            ->findAll(500);
    }

    /**
     * @return array<string, int> status => jumlah
     */
    public function rekap(int $punguanId): array
    {
        $rows = $this->keanggotaan->select('status, COUNT(*) AS n')->where('punguan_id', $punguanId)->groupBy('status')->findAll();

        return array_merge(array_fill_keys(array_keys(KeanggotaanPunguanModel::STATUS), 0), array_map('intval', array_column($rows, 'n', 'status')));
    }

    /**
     * Jumlah pengajuan yang menunggu pengesahan di punguan yang dipegang penatua ini.
     */
    public function jumlahMenunggu(User $user): int
    {
        if (! $user->can('punguan.sahkan')) {
            return 0;
        }
        $ids = $this->punguanKelolaan($user);
        if ($ids === []) {
            return 0;
        }
        $b = $this->keanggotaan->where('status', 'menunggu');
        if ($ids !== null) {
            $b->whereIn('punguan_id', $ids);
        }

        return $b->countAllResults();
    }

    /**
     * @return array<string, mixed>
     */
    private function ambilUntukPenatua(int $id, User $aktor): array
    {
        $k = $this->ambil($id);
        if (! $aktor->can('punguan.sahkan') || ! $this->bolehKelola($aktor, (int) $k['punguan_id'])) {
            throw new AksesDitolakException('Hanya Penatua punguan ini yang dapat mengesahkan atau mengubah status keanggotaan.');
        }

        return $k;
    }

    /**
     * Nomor anggota punguan berurutan: MDN-0001 (tiga huruf pertama slug punguan).
     */
    private function nomorBaru(int $punguanId): string
    {
        $slug   = (string) ((new PunguanModel())->find($punguanId)['slug'] ?? 'pgn');
        $awalan = strtoupper(substr(preg_replace('/[^a-z]/', '', $slug) ?: 'pgn', 0, 3));
        $jumlah = $this->keanggotaan->where('punguan_id', $punguanId)->where('nomor_anggota IS NOT NULL', null, false)->countAllResults();

        do {
            $nomor = sprintf('%s-%04d', $awalan, ++$jumlah);
        } while ($this->keanggotaan->where('punguan_id', $punguanId)->where('nomor_anggota', $nomor)->countAllResults() > 0);

        return $nomor;
    }
}
