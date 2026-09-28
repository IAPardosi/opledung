<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Saran pencarian anggota untuk data besar.
 *
 * Mengerti:
 *  - kode anggota (PDS-G12-000345, boleh sebagian: "G12-0003")
 *  - nama / gelar adat (awalan kata, semua kata harus cocok)
 *  - "anak ni Sabar", "boru ni Maruba": anak dari orang bernama itu
 *  - nama + huta/kota: "Poltak Medan", "Hotman Balige"
 *  - salah ketik ringan (bunyi mirip): "Hotmen" → Hotman
 *
 * Tamu hanya menerima kolom publik; member juga menerima foto, huta, domisili, dan punguan.
 */
class PencarianService
{
    public const BATAS = 12;

    private readonly BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function saran(int $margaId, string $q, bool $lengkap = false, int $batas = self::BATAS): array
    {
        $q = trim(preg_replace('/\s+/u', ' ', $q) ?? '');
        if (mb_strlen($q) < 2) {
            return [];
        }

        $ids = [];
        $tambah = static function (array $baru) use (&$ids, $batas): void {
            foreach ($baru as $id) {
                if (count($ids) >= $batas) {
                    return;
                }
                $ids[(int) $id] = true;
            }
        };

        // 1. Kode anggota.
        if (preg_match('/^([A-Za-z]{2,5}-)?G?\d{1,3}-\d{1,6}$/i', $q) || preg_match('/^[A-Za-z]{2,5}-G\d/i', $q)) {
            $tambah($this->cariIds($margaId, static fn (BaseBuilder $b) => $b->like('p.kode_anggota', strtoupper($q)), $batas));
        }

        // 2. "anak ni X" / "boru ni X".
        if (preg_match('/^(anak|boru|putra|putri)\s+(ni|dari)\s+(.+)$/iu', $q, $m)) {
            $garis = in_array(mb_strtolower($m[1]), ['boru', 'putri'], true) ? 'boru' : 'utama';
            $induk = $this->kata($m[3]);
            if ($induk !== []) {
                $tambah($this->cariIds($margaId, function (BaseBuilder $b) use ($induk, $garis): void {
                    $b->join('persons ik', 'ik.id = p.induk_id')->where('p.garis', $garis);
                    $this->cocokNama($b, 'ik', $induk);
                }, $batas));
            }

            return $this->rinci(array_keys($ids), $lengkap, $q);
        }

        $kata = $this->kata($q);
        if ($kata === []) {
            return $this->rinci(array_keys($ids), $lengkap, $q);
        }

        // 3. Semua kata cocok dengan nama / gelar.
        $tambah($this->cariIds($margaId, fn (BaseBuilder $b) => $this->cocokNama($b, 'p', $kata), $batas));

        // 4. Nama + huta/kota (kata terakhir sebagai tempat).
        if (count($ids) < $batas && count($kata) >= 2) {
            $tempat = array_pop($kata);
            $tambah($this->cariIds($margaId, function (BaseBuilder $b) use ($kata, $tempat): void {
                $this->cocokNama($b, 'p', $kata);
                $b->join('wilayah wk', 'wk.kode = p.kabupaten_kode', 'left')
                    ->groupStart()->like('p.huta', $tempat)->orLike('wk.nama', $tempat)->groupEnd();
            }, $batas));
            $kata[] = $tempat;
        }

        // 5. Bunyi mirip (salah ketik) pada kata pertama nama.
        if (count($ids) < 5 && mb_strlen($kata[0]) >= 3) {
            $tambah($this->cariIds($margaId, function (BaseBuilder $b) use ($kata): void {
                $b->where("SOUNDEX(SUBSTRING_INDEX(p.nama_lengkap, ' ', 1)) = SOUNDEX(" . $this->db->escape($kata[0]) . ')', null, false);
                foreach (array_slice($kata, 1) as $k) {
                    $b->like('p.nama_lengkap', $k);
                }
            }, $batas));
        }

        return $this->rinci(array_keys($ids), $lengkap, $q);
    }

    /**
     * @param callable(BaseBuilder): mixed $syarat
     *
     * @return list<int>
     */
    private function cariIds(int $margaId, callable $syarat, int $batas): array
    {
        $b = $this->db->table('persons p')->select('p.id')
            ->where('p.marga_id', $margaId)
            ->whereIn('p.garis', ['utama', 'boru', 'anak_boru'])
            ->where('p.deleted_at', null);
        $syarat($b);

        return array_map('intval', array_column(
            $b->orderBy("p.status_hidup = 'hidup'", 'DESC', false)->orderBy('p.generasi_ke', 'DESC')->limit($batas)->get()->getResultArray(),
            'id',
        ));
    }

    /**
     * @param list<string> $kata
     */
    private function cocokNama(BaseBuilder $b, string $alias, array $kata): void
    {
        $panjang = array_values(array_filter($kata, static fn (string $k): bool => mb_strlen($k) >= 3));
        if ($panjang !== [] && $this->db->DBDriver === 'MySQLi') {
            $boolean = implode(' ', array_map(static fn (string $k): string => '+' . $k . '*', $panjang));
            $b->where("MATCH({$alias}.nama_lengkap, {$alias}.nama_panggilan, {$alias}.gelar_adat) AGAINST(" . $this->db->escape($boolean) . ' IN BOOLEAN MODE)', null, false);
        }
        foreach (array_diff($kata, $panjang) as $k) {
            $b->like("{$alias}.nama_lengkap", $k);
        }
    }

    /**
     * @return list<string>
     */
    private function kata(string $teks): array
    {
        $abaikan = ['br', 'boru', 'op', 'ompu', 'ama', 'ni', 'pardosi'];
        $semua   = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($teks)) ?: [], static fn (string $k): bool => $k !== ''));
        $inti    = array_values(array_filter($semua, static fn (string $k): bool => ! in_array($k, $abaikan, true)));

        return $inti !== [] ? $inti : $semua;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function rinci(array $ids, bool $lengkap, string $q): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('persons p')
            ->select('p.id, p.kode_anggota, p.nama_lengkap, p.gelar_adat, p.generasi_ke, p.garis, p.jenis_kelamin, p.status_hidup,
                p.foto, p.huta, p.kabupaten_kode, ik.nama_lengkap AS nama_induk, ik.garis AS garis_induk, kk.nama_lengkap AS nama_kakek,
                wk.nama AS domisili,
                (SELECT pg.nama FROM keanggotaan_punguan kp JOIN punguan pg ON pg.id = kp.punguan_id
                  WHERE kp.person_id = p.id AND kp.status = \'aktif\' LIMIT 1) AS punguan,
                (SELECT ps.nama_lengkap FROM marriages m JOIN persons ps ON ps.id = IF(m.suami_id = p.id, m.istri_id, m.suami_id)
                  WHERE (m.suami_id = p.id OR m.istri_id = p.id) AND m.deleted_at IS NULL ORDER BY m.urutan LIMIT 1) AS pasangan', false)
            ->join('persons ik', 'ik.id = p.induk_id', 'left')
            ->join('persons kk', 'kk.id = ik.induk_id', 'left')
            ->join('wilayah wk', 'wk.kode = p.kabupaten_kode', 'left')
            ->whereIn('p.id', $ids)
            ->get()->getResultArray();

        $urut = array_flip($ids);
        $awal = mb_strtolower($q);
        usort($rows, static function (array $a, array $b) use ($urut, $awal): int {
            $pa = str_starts_with(mb_strtolower($a['nama_lengkap']), $awal) ? 0 : 1;
            $pb = str_starts_with(mb_strtolower($b['nama_lengkap']), $awal) ? 0 : 1;

            return [$pa, $urut[(int) $a['id']]] <=> [$pb, $urut[(int) $b['id']]];
        });

        // Nama yang muncul lebih dari sekali ditandai agar pembeda (ayah, sundut, huta) disorot.
        $kembar = array_count_values(array_map(static fn ($r) => mb_strtolower($r['nama_lengkap']), $rows));

        return array_map(static function (array $r) use ($lengkap, $kembar): array {
            $induk = $r['nama_induk'] ? ($r['garis'] === 'anak_boru' ? 'anak ni ' : ($r['garis'] === 'boru' ? 'boru ni ' : 'anak ni ')) . $r['nama_induk'] : null;
            $data  = [
                'id'           => (int) $r['id'],
                'kode_anggota' => $r['kode_anggota'],
                'nama_lengkap' => $r['nama_lengkap'],
                'gelar_adat'   => $r['gelar_adat'],
                'generasi_ke'  => (int) $r['generasi_ke'],
                'garis'        => $r['garis'],
                'jenis_kelamin'=> $r['jenis_kelamin'],
                'status_hidup' => $r['status_hidup'],
                'nama_induk'   => $r['nama_induk'],
                'keterangan'   => trim(implode(' · ', array_filter([$induk, $r['nama_kakek'] ? 'pahompu ni ' . $r['nama_kakek'] : null]))),
                'kembar'       => $kembar[mb_strtolower($r['nama_lengkap'])] > 1,
            ];
            if ($lengkap) {
                $data += [
                    'huta'     => $r['huta'],
                    'domisili' => $r['domisili'] ? preg_replace('/^(Kabupaten|Kota) /', '', $r['domisili']) : null,
                    'punguan'  => $r['punguan'],
                    'pasangan' => $r['pasangan'],
                    'foto'     => $r['foto'] ? site_url('anggota/' . $r['id'] . '/foto') : null,
                ];
            }

            return $data;
        }, $rows);
    }
}
