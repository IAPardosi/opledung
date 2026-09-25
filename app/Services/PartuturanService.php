<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Seeds\PartuturanSeeder;
use App\Entities\Person;
use App\Models\MarriageModel;
use App\Models\PersonModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Mesin partuturan: menentukan bagaimana A memanggil B berdasarkan pohon marga.
 *
 * Cara kerja:
 *  1. Cari titik temu (leluhur bersama terdekat) lewat closure table.
 *  2. Hitung jarak sundut A dan B ke titik temu, jenis kelamin, dan apakah
 *     salah satunya turun lewat boru (anak boru).
 *  3. Haha/anggi, amangtua/amanguda ditentukan dari garis yang lebih sulung
 *     di titik temu, bukan dari umur.
 *  4. Bila B adalah pasangan (istri/suami dari marga lain), panggilan diturunkan
 *     dari panggilan kepada suami/istrinya (mis. istri tulang → nantulang).
 *
 * Istilah diambil dari tabel partuturan sehingga dapat disesuaikan Ketua Adat.
 */
class PartuturanService
{
    /**
     * Panggilan kepada pasangan dari X, berdasarkan panggilan kepada X.
     * Nilai array: [bila X laki-laki (pasangan perempuan), bila X perempuan (pasangan laki-laki)].
     */
    private const PASANGAN = [
        'ayah'        => ['ibu', null],
        'ompung_doli' => ['ompung_boru', null],
        'ompu'        => ['ompu', 'ompu'],
        'amangtua'    => ['inangtua', null],
        'amanguda'    => ['inanguda', null],
        'inangtua'    => [null, 'amangtua'],
        'inanguda'    => [null, 'amanguda'],
        'namboru'     => [null, 'amangboru'],
        'tulang'      => ['nantulang', null],
        'anak'        => ['parumaen', null],
        'boru'        => [null, 'hela'],
        'bere'        => ['parumaen', 'hela'],
        'pahompu'     => ['pahompu', 'pahompu'],
    ];

    private readonly BaseConnection $db;

    /**
     * @var array<string, array{sebutan: string, keterangan: ?string}>|null
     */
    private ?array $istilah = null;

    public function __construct(
        private readonly PersonModel $persons = new PersonModel(),
        private readonly MarriageModel $marriages = new MarriageModel(),
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Bagaimana A memanggil B, beserta panggilan baliknya dan jalur hubungannya.
     *
     * @return array{kunci: string, sebutan: string, keterangan: ?string, balik: array{kunci: string, sebutan: string}, jalur: list<Person>, titik_temu: ?Person, rincian: string}|null
     */
    public function hubungan(int $dariId, int $keId): ?array
    {
        $a = $this->persons->find($dariId);
        $b = $this->persons->find($keId);
        if ($a === null || $b === null) {
            return null;
        }

        $maju = $this->hitung($a, $b);
        if ($maju === null) {
            return null;
        }
        $balik = $this->hitung($b, $a);

        return [
            ...$this->istilahUntuk($maju['kunci'], $maju['dari'] ?? null),
            'kunci'      => $maju['kunci'],
            'balik'      => [
                'kunci'   => $balik['kunci'] ?? 'kerabat',
                'sebutan' => $this->istilahUntuk($balik['kunci'] ?? 'kerabat', $balik['dari'] ?? null)['sebutan'],
            ],
            'jalur'      => $maju['jalur'],
            'titik_temu' => $maju['titik_temu'],
            'rincian'    => $maju['rincian'],
        ];
    }

    /**
     * @return array<string, array{sebutan: string, keterangan: ?string}>
     */
    public function semuaIstilah(): array
    {
        if ($this->istilah === null) {
            $this->istilah = [];
            foreach ($this->db->table('partuturan')->orderBy('urutan')->get()->getResultArray() as $r) {
                $this->istilah[$r['kunci']] = ['sebutan' => $r['sebutan'], 'keterangan' => $r['keterangan']];
            }
        }

        return $this->istilah;
    }

    /**
     * @return array{sebutan: string, keterangan: ?string}
     */
    private function istilahUntuk(string $kunci, ?string $dariSebutan): array
    {
        if ($kunci === 'pasangan_dari') {
            return ['sebutan' => 'Pasangan dari ' . $dariSebutan, 'keterangan' => 'Istilah khusus belum ditetapkan; tanyakan kepada Ketua Adat.'];
        }

        $semua = $this->semuaIstilah();
        if (isset($semua[$kunci])) {
            return $semua[$kunci];
        }

        [$sebutan, $keterangan] = PartuturanSeeder::ISTILAH[$kunci] ?? PartuturanSeeder::ISTILAH['kerabat'];

        return ['sebutan' => $sebutan, 'keterangan' => $keterangan];
    }

    /**
     * @return array{kunci: string, dari?: string, jalur: list<Person>, titik_temu: ?Person, rincian: string}|null
     */
    private function hitung(Person $a, Person $b): ?array
    {
        if ($a->id === $b->id) {
            return ['kunci' => 'diri', 'jalur' => [$a], 'titik_temu' => $a, 'rincian' => 'Orang yang sama.'];
        }

        // B pasangan (istri/suami dari marga lain): turunkan dari panggilan kepada suami/istrinya.
        if ($b->garis === 'pasangan') {
            return $this->hitungPasanganB($a, $b);
        }
        // A pasangan: lihat dari sudut pandang suami/istrinya.
        if ($a->garis === 'pasangan') {
            return $this->hitungPasanganA($a, $b);
        }

        return $this->hitungInti($a, $b);
    }

    /**
     * @return array{kunci: string, jalur: list<Person>, titik_temu: ?Person, rincian: string}|null
     */
    private function hitungInti(Person $a, Person $b): ?array
    {
        $temu = $this->db->query(
            'SELECT pa.ancestor_id, pa.depth AS da, pb.depth AS db
               FROM person_paths pa
               JOIN person_paths pb ON pb.ancestor_id = pa.ancestor_id
              WHERE pa.descendant_id = ? AND pb.descendant_id = ?
              ORDER BY pa.depth + pb.depth ASC
              LIMIT 1',
            [$a->id, $b->id],
        )->getRowArray();

        if ($temu === null) {
            return null;
        }

        $da    = (int) $temu['da'];
        $db    = (int) $temu['db'];
        $lca   = $this->persons->find((int) $temu['ancestor_id']);
        $jalA  = $this->jalurTurun((int) $temu['ancestor_id'], $a->id, $da);
        $jalB  = $this->jalurTurun((int) $temu['ancestor_id'], $b->id, $db);
        $jalur = [...array_reverse($jalA), $lca, ...$jalB];

        $kunci = $this->tentukanKunci($a, $b, $da, $db, $jalA[0] ?? null, $jalB[0] ?? null);

        return [
            'kunci'      => $kunci,
            'jalur'      => array_values(array_filter($jalur)),
            'titik_temu' => $lca,
            'rincian'    => $this->rincian($lca, $da, $db),
        ];
    }

    /**
     * Keturunan titik temu menuju orang tujuan: [anak titik temu, ..., tujuan].
     *
     * @return list<Person>
     */
    private function jalurTurun(int $lcaId, int $tujuanId, int $jarak): array
    {
        if ($jarak === 0) {
            return [];
        }

        return $this->persons
            ->select('persons.*')
            ->join('person_paths up', 'up.ancestor_id = persons.id AND up.descendant_id = ' . $tujuanId, 'inner', false)
            ->join('person_paths dn', 'dn.descendant_id = persons.id AND dn.ancestor_id = ' . $lcaId, 'inner', false)
            ->where('dn.depth >', 0)
            ->orderBy('dn.depth', 'ASC')
            ->findAll();
    }

    private function tentukanKunci(Person $a, Person $b, int $da, int $db, ?Person $cabangA, ?Person $cabangB): string
    {
        $lakiA  = $a->jenis_kelamin === 'L';
        $lakiB  = $b->jenis_kelamin === 'L';
        $boruA  = $a->garis === 'anak_boru';
        $boruB  = $b->garis === 'anak_boru';
        $sulung = $cabangA !== null && $cabangB !== null && $this->lebihSulung($cabangB, $cabangA);

        // B leluhur A
        if ($db === 0) {
            return match (true) {
                $da === 1 => $lakiB ? 'ayah' : 'ibu',
                $da === 2 => $lakiB ? 'ompung_doli' : 'ompung_boru',
                default   => 'ompu',
            };
        }

        // B keturunan A
        if ($da === 0) {
            return match (true) {
                $db === 1 => $lakiB ? 'anak' : 'boru',
                $db === 2 => 'pahompu',
                $db === 3 => $lakiB ? 'nini' : 'nono',
                $db === 4 => 'ondok_ondok',
                default   => 'pomparan',
            };
        }

        $selisih = $da - $db; // > 0: B lebih tua sundutnya

        if ($selisih === 0) {
            // Saudara kandung, atau keduanya anak dari boru-boru yang bersaudara.
            if ($da === 1 || ($boruA && $boruB)) {
                return $lakiA === $lakiB ? ($sulung ? 'haha' : 'anggi') : 'ito';
            }
            if ($boruB) { // B anak namboru
                return match (true) {
                    $lakiA && $lakiB  => 'lae',
                    $lakiA            => 'ito',
                    $lakiB            => 'pariban',
                    default           => 'eda',
                };
            }
            if ($boruA) { // B anak tulang
                return match (true) {
                    $lakiA && $lakiB  => 'lae',
                    $lakiA            => 'pariban',
                    $lakiB            => 'ito',
                    default           => 'eda',
                };
            }

            return $lakiA === $lakiB ? ($sulung ? 'haha' : 'anggi') : 'ito';
        }

        if ($selisih === 1) {
            if (! $boruA && ! $boruB) {
                return $lakiB ? ($sulung ? 'amangtua' : 'amanguda') : 'namboru';
            }
            if ($boruA && ! $boruB) { // setingkat ibu, semarga ibu
                return $lakiB ? 'tulang' : ($sulung ? 'inangtua' : 'inanguda');
            }
            if ($boruB && ! $boruA) { // anak namboru dari ayah
                return $lakiB ? 'amangboru' : 'namboru';
            }

            return 'kerabat';
        }

        if ($selisih === -1) {
            if (! $boruA && ! $boruB) {
                if ($lakiA) {
                    return $lakiB ? 'anak' : 'boru';
                }

                return $lakiB ? 'anak_ni_ito' : 'parumaen';
            }
            if ($boruB && ! $boruA) { // anak dari ito/saudara perempuan
                return $lakiA ? 'bere' : ($lakiB ? 'anak' : 'boru');
            }
            if ($boruA && ! $boruB && $lakiA && $lakiB) { // cucu laki-laki tulang
                return 'ompung_naposo';
            }

            return 'kerabat';
        }

        if ($selisih >= 2) {
            return $selisih === 2 ? ($lakiB ? 'ompung_doli' : 'ompung_boru') : 'ompu';
        }

        return match ($selisih) {
            -2      => 'pahompu',
            -3      => $lakiB ? 'nini' : 'nono',
            -4      => 'ondok_ondok',
            default => 'pomparan',
        };
    }

    /**
     * @return array{kunci: string, dari?: string, jalur: list<Person>, titik_temu: ?Person, rincian: string}|null
     */
    private function hitungPasanganB(Person $a, Person $b): ?array
    {
        foreach ($this->marriages->pasanganIds($b->id) as $xId) {
            $x = $this->persons->find($xId);
            if ($x === null) {
                continue;
            }
            if ($x->id === $a->id) {
                return ['kunci' => $a->jenis_kelamin === 'L' ? 'istri' : 'suami', 'jalur' => [$a, $b], 'titik_temu' => $a, 'rincian' => 'Pasangan Anda.'];
            }

            $keX = $this->hitungInti($a, $x);
            if ($keX === null) {
                continue;
            }

            $kunci = $this->kunciPasangan($keX['kunci'], $x, $a);
            $hasil = [...$keX, 'jalur' => [...$keX['jalur'], $b], 'rincian' => $keX['rincian'] . ' Lalu melalui pernikahan dengan ' . $x->nama_lengkap . '.'];

            return $kunci === null
                ? [...$hasil, 'kunci' => 'pasangan_dari', 'dari' => $this->istilahUntuk($keX['kunci'], null)['sebutan']]
                : [...$hasil, 'kunci' => $kunci];
        }

        return null;
    }

    /**
     * A adalah pasangan (mis. istri): panggilan mengikuti suaminya dengan beberapa penyesuaian.
     *
     * @return array{kunci: string, dari?: string, jalur: list<Person>, titik_temu: ?Person, rincian: string}|null
     */
    private function hitungPasanganA(Person $a, Person $b): ?array
    {
        foreach ($this->marriages->pasanganIds($a->id) as $yId) {
            $y = $this->persons->find($yId);
            if ($y === null) {
                continue;
            }
            if ($y->id === $b->id) {
                return ['kunci' => $a->jenis_kelamin === 'L' ? 'istri' : 'suami', 'jalur' => [$a, $b], 'titik_temu' => $b, 'rincian' => 'Pasangan Anda.'];
            }

            $dariY = $b->garis === 'pasangan' ? $this->hitungPasanganB($y, $b) : $this->hitungInti($y, $b);
            if ($dariY === null) {
                continue;
            }

            $kunci = match ($dariY['kunci']) {
                'ayah'                              => 'simatua_doli',
                'ibu'                               => 'simatua_boru',
                'anak', 'boru', 'pahompu', 'nini',
                'nono', 'ondok_ondok', 'pomparan',
                'ompung_doli', 'ompung_boru', 'ompu' => $dariY['kunci'],
                'ito'                               => $a->jenis_kelamin === 'P' ? 'eda' : 'lae',
                default                             => null,
            };

            $hasil = [...$dariY, 'jalur' => [$a, ...$dariY['jalur']], 'rincian' => 'Melalui pasangan Anda, ' . $y->nama_lengkap . '. ' . $dariY['rincian']];

            return $kunci === null
                ? [...$hasil, 'kunci' => 'pasangan_dari', 'dari' => $this->istilahUntuk($dariY['kunci'], $dariY['dari'] ?? null)['sebutan'] . ' dari pasangan']
                : [...$hasil, 'kunci' => $kunci];
        }

        return null;
    }

    private function kunciPasangan(string $kunciX, Person $x, Person $a): ?string
    {
        $lakiX = $x->jenis_kelamin === 'L';

        if (isset(self::PASANGAN[$kunciX])) {
            return self::PASANGAN[$kunciX][$lakiX ? 0 : 1];
        }

        return match ($kunciX) {
            'haha'  => $lakiX ? 'angkang_boru' : 'lae',
            'anggi' => $lakiX ? 'anggi_boru' : 'lae',
            'ito'   => $lakiX ? 'eda' : 'lae',
            // Istri lae dari pihak tulang adalah inangbao (istri hula-hula).
            'lae'   => $x->garis !== 'anak_boru' && $a->garis === 'anak_boru' ? 'inang_bao' : null,
            default => null,
        };
    }

    /**
     * Garis siapa yang lebih sulung di titik temu: urutan anak, lalu tahun lahir, lalu nomor urut data.
     */
    private function lebihSulung(Person $x, Person $y): bool
    {
        $kunci = static fn (Person $p): array => [$p->urutan_anak ?? PHP_INT_MAX, $p->tahun_lahir ?? PHP_INT_MAX, $p->id];

        return $kunci($x) < $kunci($y);
    }

    private function rincian(?Person $lca, int $da, int $db): string
    {
        if ($lca === null) {
            return '';
        }
        if ($db === 0) {
            return "Dia leluhur langsung Anda, {$da} sundut di atas Anda.";
        }
        if ($da === 0) {
            return "Dia keturunan langsung Anda, {$db} sundut di bawah Anda.";
        }

        return sprintf(
            'Titik temu: %s (Generasi %d). Anda %d sundut di bawahnya, dia %d sundut di bawahnya.',
            $lca->nama_lengkap,
            $lca->generasi_ke,
            $da,
            $db,
        );
    }
}
