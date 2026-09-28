<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Models\MarriageModel;
use App\Models\PersonModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Query baca silsilah memakai closure table (person_paths),
 * sehingga jalur leluhur dan keturunan cukup satu query berindeks.
 */
class SilsilahQuery
{
    /**
     * Kolom yang aman ditampilkan pada pohon publik (tanpa login).
     */
    public const KOLOM_PUBLIK = ['id', 'kode_anggota', 'nama_lengkap', 'gelar_adat', 'generasi_ke', 'garis', 'jenis_kelamin', 'status_hidup', 'induk_id', 'urutan_anak'];

    /**
     * Batas node pada mapping mode lengkap.
     */
    public const BATAS_LENGKAP = 1500;

    public const MODE_MAPPING = ['fokus', 'keluarga', 'lengkap'];

    private readonly BaseConnection $db;

    public function __construct(
        private readonly PersonModel $persons = new PersonModel(),
        private readonly MarriageModel $marriages = new MarriageModel(),
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Jalur dari Generasi 1 sampai orang ini (urut dari leluhur tertua).
     *
     * @return list<Person>
     */
    public function jalurLeluhur(int $personId): array
    {
        return $this->persons
            ->select('persons.*')
            ->join('person_paths pp', 'pp.ancestor_id = persons.id')
            ->where('pp.descendant_id', $personId)
            ->orderBy('pp.depth', 'DESC')
            ->findAll();
    }

    /**
     * Keturunan sampai kedalaman tertentu (tidak termasuk diri sendiri).
     *
     * @return list<Person>
     */
    public function keturunan(int $personId, int $kedalaman = 3): array
    {
        return $this->persons
            ->select('persons.*')
            ->join('person_paths pp', 'pp.descendant_id = persons.id')
            ->where('pp.ancestor_id', $personId)
            ->where('pp.depth >', 0)
            ->where('pp.depth <=', $kedalaman)
            ->orderBy('persons.generasi_ke', 'ASC')
            ->orderBy('persons.induk_id', 'ASC')
            ->orderBy('persons.urutan_anak', 'ASC')
            ->findAll();
    }

    /**
     * Jumlah seluruh keturunan (semua generasi) yang masih aktif.
     */
    public function jumlahKeturunan(int $personId): int
    {
        return $this->db->table('person_paths pp')
            ->join('persons p', 'p.id = pp.descendant_id')
            ->where('pp.ancestor_id', $personId)
            ->where('pp.depth >', 0)
            ->where('p.deleted_at', null)
            ->countAllResults();
    }

    /**
     * Pohon bersarang untuk tampilan: node akar + keturunan sampai $kedalaman.
     * Node di batas kedalaman diberi 'punya_anak' agar bisa dimuat lanjut (lazy load).
     *
     * @return array<string, mixed>|null
     */
    public function pohon(int $akarId, int $kedalaman = 3, bool $publik = true): ?array
    {
        $akar = $this->persons->find($akarId);
        if ($akar === null) {
            return null;
        }

        $semua = [$akar, ...$this->keturunan($akarId, $kedalaman)];
        $ids   = array_map(static fn (Person $p): int => $p->id, $semua);

        $punyaAnak = array_flip(array_map('intval', array_column(
            $this->db->table('persons')->select('induk_id')->distinct()
                ->whereIn('induk_id', $ids)->where('deleted_at', null)
                ->get()->getResultArray(),
            'induk_id',
        )));

        $nodes = [];
        foreach ($semua as $p) {
            $row = $publik
                ? array_intersect_key($p->toArray(), array_flip(self::KOLOM_PUBLIK))
                : $p->toPublicArray();
            $row['punya_anak'] = isset($punyaAnak[$p->id]);
            $row['ibu_id']     = $p->ibu_id;
            $row['anak']       = [];
            $nodes[$p->id]     = $row;
        }
        $this->lengkapiKeluarga($nodes);

        // Keturunan sudah terurut per generasi, jadi induk selalu diproses lebih dulu.
        foreach (array_reverse(array_keys($nodes)) as $id) {
            $indukId = $nodes[$id]['induk_id'];
            if ($id !== $akarId && $indukId !== null && isset($nodes[$indukId])) {
                array_unshift($nodes[$indukId]['anak'], $nodes[$id]);
            }
        }

        return $nodes[$akarId];
    }

    /**
     * Mapping keturunan: pohon dari leluhur (Sundut 1 atau sundut pilihan) sampai orang ini.
     *
     *  - fokus    : hanya garis lurus; saudara di setiap sundut dilipat ("+N")
     *  - keluarga : garis lurus + saudara di setiap sundut + anak dan cucu orang ini
     *  - lengkap  : seluruh cabang dari sundut awal sampai sundut orang ini (+1),
     *               dipangkas bila melebihi BATAS_LENGKAP agar tetap ringan
     *
     * Setiap node membawa 'di_jalur', 'target', 'jumlah_anak', dan 'tersembunyi'
     * (anak yang belum dimuat, bisa dibuka lewat /api/pohon).
     *
     * @return array{akar: array<string, mixed>, jalur: list<Person>, target: Person, mulai: Person, jumlah: int, dipangkas: bool}|null
     */
    public function mapping(int $targetId, string $mode = 'fokus', ?int $mulaiGenerasi = null): ?array
    {
        $jalur  = $this->jalurLeluhur($targetId);
        $target = end($jalur) ?: null;
        if ($target === null || $target->id !== $targetId || $target->garis === 'pasangan') {
            return null;
        }

        $mulai = $jalur[0];
        foreach ($jalur as $p) {
            if ($mulaiGenerasi !== null && $p->generasi_ke === $mulaiGenerasi) {
                $mulai = $p;
            }
        }
        $jalurDipakai = array_values(array_filter($jalur, static fn (Person $p): bool => $p->generasi_ke >= $mulai->generasi_ke));
        $jalurIds     = array_map(static fn (Person $p): int => $p->id, $jalurDipakai);

        $semua     = $jalurDipakai;
        $dipangkas = false;

        if ($mode === 'keluarga' || $mode === 'lengkap') {
            $semua = [...$semua, ...$this->persons->whereIn('induk_id', $jalurIds)->findAll(), ...$this->keturunan($targetId, 2)];
        }

        if ($mode === 'lengkap') {
            $kedalaman = $target->generasi_ke - $mulai->generasi_ke + 1;
            while ($kedalaman > 1 && $this->jumlahKeturunanSampai($mulai->id, $kedalaman) > self::BATAS_LENGKAP) {
                $kedalaman--;
                $dipangkas = true;
            }
            $semua = [...$semua, ...$this->keturunan($mulai->id, $kedalaman)];
        }

        // Unik, lalu urut per sundut agar induk selalu diproses lebih dulu.
        $unik = [];
        foreach ($semua as $p) {
            $unik[$p->id] = $p;
        }
        uasort($unik, static fn (Person $a, Person $b): int => [$a->generasi_ke, $a->induk_id ?? 0, $a->urutan_anak ?? 99, $a->id]
            <=> [$b->generasi_ke, $b->induk_id ?? 0, $b->urutan_anak ?? 99, $b->id]);

        $jumlahAnak = array_map('intval', array_column(
            $this->db->table('persons')->select('induk_id, COUNT(*) AS n')
                ->whereIn('induk_id', array_keys($unik))->where('deleted_at', null)
                ->groupBy('induk_id')->get()->getResultArray(),
            'n',
            'induk_id',
        ));

        $diJalur = array_flip($jalurIds);
        $nodes   = [];
        foreach ($unik as $p) {
            $row                = array_intersect_key($p->toArray(), array_flip(self::KOLOM_PUBLIK));
            $row['jumlah_anak'] = $jumlahAnak[$p->id] ?? 0;
            $row['punya_anak']  = $row['jumlah_anak'] > 0;
            $row['di_jalur']    = isset($diJalur[$p->id]);
            $row['target']      = $p->id === $target->id;
            $row['ibu_id']      = $p->ibu_id;
            $row['anak']        = [];
            $nodes[$p->id]      = $row;
        }
        $this->lengkapiKeluarga($nodes);

        foreach (array_reverse(array_keys($nodes)) as $id) {
            $indukId = $nodes[$id]['induk_id'];
            if ($id !== $mulai->id && $indukId !== null && isset($nodes[$indukId])) {
                array_unshift($nodes[$indukId]['anak'], $nodes[$id]);
            }
        }

        // Hitung anak yang belum ikut dimuat (dari atas ke bawah pada salinan bersarang).
        $akar = $this->tandaiTersembunyi($nodes[$mulai->id]);

        return [
            'akar'      => $akar,
            'jalur'     => $jalur,
            'target'    => $target,
            'mulai'     => $mulai,
            'jumlah'    => count($nodes),
            'dipangkas' => $dipangkas,
        ];
    }

    /**
     * Kotak keluarga: setiap node membawa pasangannya (istri boleh lebih dari satu,
     * urut pernikahan ke-), dan anak membawa 'ibu_ke' bila ayahnya beristri lebih dari satu.
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    private function lengkapiKeluarga(array &$nodes): void
    {
        if ($nodes === []) {
            return;
        }
        $ids  = array_keys($nodes);
        $rows = $this->db->table('marriages m')
            ->select('m.suami_id, m.istri_id, m.urutan, m.status, p.id, p.nama_lengkap, p.marga_nama, p.status_hidup, p.jenis_kelamin')
            ->join('persons p', 'p.id = IF(m.suami_id IN (' . implode(',', $ids) . '), m.istri_id, m.suami_id)', '', false)
            ->groupStart()->whereIn('m.suami_id', $ids)->orWhereIn('m.istri_id', $ids)->groupEnd()
            ->where('m.deleted_at', null)->where('p.deleted_at', null)
            ->orderBy('m.urutan')->orderBy('m.id')
            ->get()->getResultArray();

        foreach ($ids as $id) {
            $nodes[$id]['pasangan'] = [];
        }
        foreach ($rows as $r) {
            $pemilik = isset($nodes[(int) $r['suami_id']]) ? (int) $r['suami_id'] : (int) $r['istri_id'];
            if ((int) $r['id'] === $pemilik) {
                continue;
            }
            $nodes[$pemilik]['pasangan'][] = [
                'id'          => (int) $r['id'],
                'nama'        => $r['nama_lengkap'],
                'marga'       => $r['marga_nama'],
                'ke'          => count($nodes[$pemilik]['pasangan']) + 1,
                'status_hidup'=> $r['status_hidup'],
                'cerai'       => $r['status'] === 'cerai_hidup',
            ];
        }

        foreach ($nodes as $id => $n) {
            $induk = $n['induk_id'] !== null ? ($nodes[$n['induk_id']] ?? null) : null;
            $nodes[$id]['ibu_ke'] = null;
            if ($induk !== null && count($induk['pasangan']) > 1 && $n['ibu_id'] !== null) {
                foreach ($induk['pasangan'] as $ps) {
                    if ($ps['id'] === (int) $n['ibu_id']) {
                        $nodes[$id]['ibu_ke'] = $ps['ke'];
                    }
                }
            }
            unset($nodes[$id]['ibu_id']);
        }
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    private function tandaiTersembunyi(array $node): array
    {
        $node['anak']        = array_map($this->tandaiTersembunyi(...), $node['anak']);
        $node['tersembunyi'] = max(0, $node['jumlah_anak'] - count($node['anak']));

        return $node;
    }

    private function jumlahKeturunanSampai(int $personId, int $kedalaman): int
    {
        return $this->db->table('person_paths pp')
            ->join('persons p', 'p.id = pp.descendant_id')
            ->where('pp.ancestor_id', $personId)
            ->where('pp.depth <=', $kedalaman)
            ->where('p.deleted_at', null)
            ->countAllResults();
    }

    /**
     * @return list<array<string, mixed>> data pasangan beserta info pernikahan
     */
    public function pasangan(int $personId): array
    {
        return $this->db->table('marriages m')
            ->select('p.*, m.urutan AS pernikahan_ke, m.tanggal_nikah, m.status AS status_pernikahan, m.id AS marriage_id')
            ->join('persons p', 'p.id = IF(m.suami_id = ' . $personId . ', m.istri_id, m.suami_id)', '', false)
            ->groupStart()->where('m.suami_id', $personId)->orWhere('m.istri_id', $personId)->groupEnd()
            ->where('m.deleted_at', null)
            ->where('p.deleted_at', null)
            ->orderBy('m.urutan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Semua data untuk halaman profil anggota.
     *
     * @return array<string, mixed>|null
     */
    public function profil(int $personId): ?array
    {
        $person = $this->persons->find($personId);
        if ($person === null) {
            return null;
        }

        return [
            'person'           => $person,
            'ayah'             => $person->ayah_id ? $this->persons->find($person->ayah_id) : null,
            'ibu'              => $person->ibu_id ? $this->persons->find($person->ibu_id) : null,
            'pasangan'         => $this->pasangan($person->id),
            'anak'             => $this->persons->anak($person->id),
            'saudara'          => $this->persons->saudaraKandung($person),
            'jalur'            => $this->jalurLeluhur($person->id),
            'jumlah_keturunan' => $this->jumlahKeturunan($person->id),
        ];
    }

    /**
     * Ringkasan jumlah anggota per generasi untuk satu marga.
     *
     * @return list<array{generasi_ke: int, utama: int, boru: int, hidup: int}>
     */
    public function rekapGenerasi(int $margaId): array
    {
        $rows = $this->db->table('persons')
            ->select("generasi_ke,
                SUM(garis = 'utama') AS utama,
                SUM(garis = 'boru') AS boru,
                SUM(status_hidup = 'hidup' AND garis IN ('utama','boru')) AS hidup", false)
            ->where('marga_id', $margaId)
            ->whereIn('garis', ['utama', 'boru'])
            ->where('deleted_at', null)
            ->groupBy('generasi_ke')
            ->orderBy('generasi_ke', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $r): array => array_map('intval', $r), $rows);
    }

    /**
     * Keluarga dekat di sekitar seseorang: 2 sundut ke atas, sesundut, dan 2 sundut ke bawah.
     *
     * @return array<string, mixed>|null
     */
    public function keluargaDekat(int $personId): ?array
    {
        $p = $this->persons->find($personId);
        if ($p === null) {
            return null;
        }

        $orangTua = $p->induk_id ? $this->persons->find($p->induk_id) : null;
        $ompung   = $orangTua?->induk_id ? $this->persons->find($orangTua->induk_id) : null;
        $anak     = $this->persons->anak($p->id);
        $pahompu  = [];
        foreach ($anak as $a) {
            foreach ($this->persons->anak($a->id) as $c) {
                $pahompu[] = $c;
            }
        }

        return [
            'person'          => $p,
            'ompung'          => $ompung,
            'ompungPasangan'  => $ompung ? $this->pasangan($ompung->id) : [],
            'orangTua'        => $orangTua,
            'orangTuaPasangan'=> $orangTua ? $this->pasangan($orangTua->id) : [],
            'saudaraOrangTua' => $orangTua ? $this->persons->saudaraKandung($orangTua) : [],
            'saudara'         => $this->persons->saudaraKandung($p),
            'pasangan'        => $this->pasangan($p->id),
            'anak'            => $anak,
            'pahompu'         => array_slice($pahompu, 0, 40),
            'jumlahPahompu'   => count($pahompu),
        ];
    }

    /**
     * Jumlah saudara kandung setiap orang di jalur (untuk tampilan "Jalur saya").
     *
     * @param list<Person> $jalur
     *
     * @return array<int, int> person id => jumlah saudara
     */
    public function jumlahSaudara(array $jalur): array
    {
        $induk = array_values(array_filter(array_map(static fn (Person $p): ?int => $p->induk_id, $jalur)));
        if ($induk === []) {
            return [];
        }

        $jumlah = array_column(
            $this->db->table('persons')->select('induk_id, COUNT(*) AS n')->whereIn('induk_id', $induk)->where('deleted_at', null)->groupBy('induk_id')->get()->getResultArray(),
            'n',
            'induk_id',
        );

        $hasil = [];
        foreach ($jalur as $p) {
            $hasil[$p->id] = $p->induk_id ? max(0, (int) ($jumlah[$p->induk_id] ?? 1) - 1) : 0;
        }

        return $hasil;
    }
}
