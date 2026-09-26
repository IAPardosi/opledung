<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AksesDitolakException;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\KeanggotaanPunguanModel;
use App\Models\KeuanganCatatanModel;
use App\Models\KeuanganKategoriModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Config\Database;

/**
 * Keuangan sederhana punguan: hanya untuk Member Punguan aktif.
 *
 * Alur: Humas mencatat (status 'menunggu') → Penatua punguan memvalidasi ('sah' / 'ditolak').
 * Catatan yang dibuat Penatua sendiri langsung sah. Catatan sah tidak dapat diubah lagi.
 */
class KeuanganService
{
    private readonly BaseConnection $db;

    public function __construct(
        private readonly KeuanganCatatanModel $catatan = new KeuanganCatatanModel(),
        private readonly KeuanganKategoriModel $kategori = new KeuanganKategoriModel(),
        private readonly KeanggotaanPunguanModel $keanggotaan = new KeanggotaanPunguanModel(),
        private readonly PunguanService $punguan = new PunguanService(),
        private readonly AuditLogger $audit = new AuditLogger(),
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Menambah atau mengubah kategori keuangan punguan.
     *
     * @param array<string, mixed> $data
     */
    public function simpanKategori(User $aktor, int $punguanId, array $data, ?int $id = null): int
    {
        $this->pastikanPencatat($aktor, $punguanId);

        $lama = null;
        if ($id !== null) {
            $lama = $this->kategori->find($id);
            if ($lama === null || (int) $lama['punguan_id'] !== $punguanId) {
                throw new SilsilahException('Kategori tidak ditemukan.');
            }
        }

        $baris = [
            'punguan_id'      => $punguanId,
            'nama'            => trim((string) ($data['nama'] ?? '')),
            'jenis'           => (string) ($data['jenis'] ?? 'sekali'),
            'nominal_standar' => ($data['nominal_standar'] ?? '') === '' ? null : (int) preg_replace('/\D/', '', (string) $data['nominal_standar']),
            'keterangan'      => trim((string) ($data['keterangan'] ?? '')) ?: null,
            'urutan'          => (int) ($data['urutan'] ?? 0),
            'is_active'       => empty($data['is_active']) ? 0 : 1,
        ];

        $ok = $id === null ? $this->kategori->insert($baris) : $this->kategori->update($id, $baris);
        if ($ok === false) {
            throw new ValidasiDataException($this->kategori->errors());
        }
        $id ??= (int) $this->kategori->getInsertID();
        $this->audit->catat($lama === null ? 'tambah' : 'ubah', 'keuangan_kategori', $id, $lama, $baris, $aktor->id);

        return $id;
    }

    /**
     * Mencatat pembayaran. Untuk kategori bulanan, $data['periode_sampai'] dapat diisi
     * agar beberapa bulan tercatat sekaligus (satu catatan per bulan).
     *
     * @param array<string, mixed> $data keanggotaan_id, kategori_id, periode, periode_sampai, tanggal, nominal, metode, keterangan
     *
     * @return list<int> ID catatan yang dibuat
     */
    public function catat(User $aktor, array $data): array
    {
        $k = $this->keanggotaan->find((int) ($data['keanggotaan_id'] ?? 0));
        if ($k === null) {
            throw new ValidasiDataException(['keanggotaan_id' => 'Pilih member punguan.']);
        }
        if ($k['status'] !== 'aktif') {
            throw new SilsilahException('Catatan keuangan hanya untuk Member Punguan yang aktif. Anggota ini hanya Member Marga atau keanggotaannya belum disahkan.');
        }
        $punguanId = (int) $k['punguan_id'];
        $this->pastikanPencatat($aktor, $punguanId);

        $kat = $this->kategori->find((int) ($data['kategori_id'] ?? 0));
        if ($kat === null || (int) $kat['punguan_id'] !== $punguanId || ! $kat['is_active']) {
            throw new ValidasiDataException(['kategori_id' => 'Pilih kategori keuangan punguan ini.']);
        }

        $nominal = (int) preg_replace('/\D/', '', (string) ($data['nominal'] ?? ''));
        $tanggal = (string) ($data['tanggal'] ?? '');
        $galat   = [];
        if ($nominal <= 0) {
            $galat['nominal'] = 'Nominal harus lebih dari 0.';
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || strtotime($tanggal) === false) {
            $galat['tanggal'] = 'Tanggal bayar tidak valid.';
        } elseif ($tanggal > Time::today()->toDateString()) {
            $galat['tanggal'] = 'Tanggal bayar tidak boleh di masa depan.';
        }

        $periode = $this->daftarPeriode($kat['jenis'], (string) ($data['periode'] ?? ''), (string) ($data['periode_sampai'] ?? ''), $galat);
        if ($galat !== []) {
            throw new ValidasiDataException($galat);
        }

        foreach ($periode as $p) {
            if ($p !== null && $this->sudahTercatat((int) $k['id'], (int) $kat['id'], $p)) {
                throw new SilsilahException(sprintf('%s periode %s sudah tercatat untuk anggota ini.', $kat['nama'], self::labelPeriode($p)));
            }
        }

        $langsungSah = $aktor->can('keuangan.validasi');
        $ids         = [];
        $this->db->transStart();
        foreach ($periode as $p) {
            $baris = [
                'punguan_id'      => $punguanId,
                'keanggotaan_id'  => (int) $k['id'],
                'person_id'       => (int) $k['person_id'],
                'kategori_id'     => (int) $kat['id'],
                'periode'         => $p,
                'tanggal'         => $tanggal,
                'nominal'         => $nominal,
                'metode'          => array_key_exists((string) ($data['metode'] ?? ''), KeuanganCatatanModel::METODE) ? $data['metode'] : 'tunai',
                'keterangan'      => trim((string) ($data['keterangan'] ?? '')) ?: null,
                'status'          => $langsungSah ? 'sah' : 'menunggu',
                'dicatat_oleh'    => $aktor->id,
                'divalidasi_oleh' => $langsungSah ? $aktor->id : null,
                'divalidasi_at'   => $langsungSah ? Time::now()->toDateTimeString() : null,
            ];
            $id    = (int) $this->catatan->insert($baris);
            $ids[] = $id;
            $this->audit->catat('catat', 'keuangan_catatan', $id, null, $baris, $aktor->id);
        }
        $this->db->transComplete();

        return $ids;
    }

    /**
     * Penatua memvalidasi catatan: sah atau ditolak (dengan alasan).
     */
    public function validasi(int $id, User $aktor, bool $sah, ?string $alasan = null): void
    {
        $c = $this->ambil($id);
        if (! $aktor->can('keuangan.validasi') || ! $this->punguan->bolehKelola($aktor, (int) $c['punguan_id'])) {
            throw new AksesDitolakException('Hanya Penatua punguan ini yang dapat memvalidasi catatan keuangan.');
        }
        if ($c['status'] !== 'menunggu') {
            throw new SilsilahException('Catatan ini sudah divalidasi.');
        }
        if (! $sah && trim((string) $alasan) === '') {
            throw new SilsilahException('Tuliskan alasan penolakan agar Humas dapat memperbaikinya.');
        }

        $data = [
            'status'          => $sah ? 'sah' : 'ditolak',
            'alasan_tolak'    => $sah ? null : trim((string) $alasan),
            'divalidasi_oleh' => $aktor->id,
            'divalidasi_at'   => Time::now()->toDateTimeString(),
        ];
        $this->catatan->update($id, $data);
        $this->audit->catat($sah ? 'sahkan' : 'tolak', 'keuangan_catatan', $id, ['status' => 'menunggu'], $data, $aktor->id);
    }

    /**
     * Validasi sekaligus beberapa catatan (semua sah).
     *
     * @param list<int> $ids
     */
    public function validasiBanyak(array $ids, User $aktor): int
    {
        $n = 0;
        foreach ($ids as $id) {
            $c = $this->catatan->find($id);
            if ($c !== null && $c['status'] === 'menunggu') {
                $this->validasi((int) $id, $aktor, true);
                $n++;
            }
        }

        return $n;
    }

    /**
     * Menghapus catatan yang belum sah (salah input). Catatan sah tidak dapat dihapus.
     */
    public function hapus(int $id, User $aktor): void
    {
        $c = $this->ambil($id);
        $this->pastikanPencatat($aktor, (int) $c['punguan_id']);
        if ($c['status'] === 'sah') {
            throw new SilsilahException('Catatan yang sudah sah tidak dapat dihapus.');
        }
        $this->catatan->delete($id);
        $this->audit->catat('hapus', 'keuangan_catatan', $id, $c, null, $aktor->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function ambil(int $id): array
    {
        $c = $this->catatan->lengkap()->find($id);
        if ($c === null) {
            throw new SilsilahException('Catatan keuangan tidak ditemukan.');
        }

        return $c;
    }

    /**
     * Rekap kategori bulanan satu tahun: baris per member punguan aktif, kolom per bulan.
     *
     * @return list<array{keanggotaan_id: int, person_id: int, nama: string, kode: string, nomor: ?string, bulan: array<int, ?string>, total: int, tunggakan: int}>
     */
    public function rekapBulanan(int $punguanId, int $kategoriId, int $tahun): array
    {
        $anggota = $this->keanggotaan
            ->select('keanggotaan_punguan.id, keanggotaan_punguan.person_id, keanggotaan_punguan.nomor_anggota, keanggotaan_punguan.tanggal_masuk, keanggotaan_punguan.tanggal_keluar, persons.nama_lengkap, persons.kode_anggota')
            ->join('persons', 'persons.id = keanggotaan_punguan.person_id')
            ->where('keanggotaan_punguan.punguan_id', $punguanId)
            ->groupStart()
                ->where('keanggotaan_punguan.status', 'aktif')
                ->orGroupStart()->where('keanggotaan_punguan.status', 'nonaktif')->where('keanggotaan_punguan.tanggal_keluar >=', $tahun . '-01-01')->groupEnd()
            ->groupEnd()
            ->orderBy('persons.nama_lengkap')
            ->findAll();

        $catatan = $this->catatan->select('keanggotaan_id, periode, status, nominal')
            ->where('punguan_id', $punguanId)->where('kategori_id', $kategoriId)
            ->like('periode', $tahun . '-', 'after')
            ->where('status !=', 'ditolak')
            ->findAll();
        $peta = [];
        foreach ($catatan as $c) {
            $peta[(int) $c['keanggotaan_id']][(int) substr((string) $c['periode'], 5, 2)] = $c;
        }

        $sekarang = Time::today();
        $hasil    = [];
        foreach ($anggota as $a) {
            $bulan     = [];
            $total     = 0;
            $tunggakan = 0;
            for ($m = 1; $m <= 12; $m++) {
                $periode = sprintf('%04d-%02d', $tahun, $m);
                $c       = $peta[(int) $a['id']][$m] ?? null;
                $wajib   = $periode >= substr((string) ($a['tanggal_masuk'] ?: $tahun . '-01-01'), 0, 7)
                    && ($a['tanggal_keluar'] === null || $periode <= substr((string) $a['tanggal_keluar'], 0, 7))
                    && $periode <= $sekarang->format('Y-m');
                $bulan[$m] = $c['status'] ?? ($wajib ? 'belum' : null);
                if ($c !== null && $c['status'] === 'sah') {
                    $total += (int) $c['nominal'];
                }
                if ($c === null && $wajib) {
                    $tunggakan++;
                }
            }
            $hasil[] = [
                'keanggotaan_id' => (int) $a['id'],
                'person_id'      => (int) $a['person_id'],
                'nama'           => $a['nama_lengkap'],
                'kode'           => $a['kode_anggota'],
                'nomor'          => $a['nomor_anggota'],
                'bulan'          => $bulan,
                'total'          => $total,
                'tunggakan'      => $tunggakan,
            ];
        }

        return $hasil;
    }

    /**
     * Ringkasan per kategori dalam satu tahun (berdasarkan tanggal bayar).
     *
     * @return list<array{id: int, nama: string, jenis: string, sah: int, menunggu: int, jumlah_menunggu: int}>
     */
    public function ringkasan(int $punguanId, int $tahun): array
    {
        $rows = $this->db->table('keuangan_kategori k')
            ->select("k.id, k.nama, k.jenis,
                COALESCE(SUM(CASE WHEN c.status = 'sah' THEN c.nominal END), 0) AS sah,
                COALESCE(SUM(CASE WHEN c.status = 'menunggu' THEN c.nominal END), 0) AS menunggu,
                COUNT(CASE WHEN c.status = 'menunggu' THEN 1 END) AS jumlah_menunggu", false)
            ->join('keuangan_catatan c', "c.kategori_id = k.id AND c.deleted_at IS NULL AND YEAR(c.tanggal) = {$tahun}", 'left', false)
            ->where('k.punguan_id', $punguanId)
            ->groupBy('k.id')
            ->orderBy('k.urutan')->orderBy('k.nama')
            ->get()->getResultArray();

        return array_map(static fn (array $r): array => [
            'id'              => (int) $r['id'],
            'nama'            => $r['nama'],
            'jenis'           => $r['jenis'],
            'sah'             => (int) $r['sah'],
            'menunggu'        => (int) $r['menunggu'],
            'jumlah_menunggu' => (int) $r['jumlah_menunggu'],
        ], $rows);
    }

    /**
     * Riwayat keuangan seseorang (untuk profil / Keuangan saya).
     *
     * @return list<array<string, mixed>>
     */
    public function riwayat(int $personId, int $batas = 100, ?int $punguanId = null): array
    {
        $b = $this->catatan->lengkap()->where('keuangan_catatan.person_id', $personId);
        if ($punguanId !== null) {
            $b->where('keuangan_catatan.punguan_id', $punguanId);
        }

        return $b
            ->orderBy('keuangan_catatan.tanggal', 'DESC')->orderBy('keuangan_catatan.id', 'DESC')
            ->findAll($batas);
    }

    public function jumlahMenunggu(User $user): int
    {
        if (! $user->can('keuangan.validasi')) {
            return 0;
        }
        $ids = $this->punguan->punguanKelolaan($user);
        if ($ids === []) {
            return 0;
        }
        $b = $this->catatan->where('status', 'menunggu');
        if ($ids !== null) {
            $b->whereIn('punguan_id', $ids);
        }

        return $b->countAllResults();
    }

    public static function labelPeriode(?string $periode): string
    {
        if ($periode === null || $periode === '') {
            return '–';
        }
        if (strlen($periode) === 4) {
            return $periode;
        }
        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $bulan[(int) substr($periode, 5, 2)] . ' ' . substr($periode, 0, 4);
    }

    public static function rupiah(int|string|null $n): string
    {
        return 'Rp' . number_format((int) $n, 0, ',', '.');
    }

    private function pastikanPencatat(User $aktor, int $punguanId): void
    {
        if (! $aktor->can('keuangan.catat') || ! $this->punguan->bolehKelola($aktor, $punguanId)) {
            throw new AksesDitolakException('Anda tidak berhak mencatat keuangan punguan ini.');
        }
    }

    private function sudahTercatat(int $keanggotaanId, int $kategoriId, string $periode): bool
    {
        return $this->catatan->where('keanggotaan_id', $keanggotaanId)->where('kategori_id', $kategoriId)
            ->where('periode', $periode)->where('status !=', 'ditolak')->countAllResults() > 0;
    }

    /**
     * @param array<string, string> $galat
     *
     * @return list<string|null>
     */
    private function daftarPeriode(string $jenis, string $dari, string $sampai, array &$galat): array
    {
        if ($jenis === 'sekali') {
            return [null];
        }
        if ($jenis === 'tahunan') {
            if (! preg_match('/^\d{4}$/', $dari)) {
                $galat['periode'] = 'Isi tahun periode (mis. 2026).';

                return [];
            }

            return [$dari];
        }

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $dari)) {
            $galat['periode'] = 'Pilih bulan periode.';

            return [];
        }
        $sampai = $sampai === '' ? $dari : $sampai;
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $sampai) || $sampai < $dari) {
            $galat['periode_sampai'] = 'Periode akhir harus sama atau setelah periode awal.';

            return [];
        }

        $hasil = [];
        for ($t = strtotime($dari . '-01'); date('Y-m', $t) <= $sampai; $t = strtotime('+1 month', $t)) {
            $hasil[] = date('Y-m', $t);
            if (count($hasil) > 24) {
                $galat['periode_sampai'] = 'Paling banyak 24 bulan sekaligus.';

                return [];
            }
        }

        return $hasil;
    }
}
