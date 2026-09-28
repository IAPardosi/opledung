<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Services\PersonService;
use CodeIgniter\Database\Seeder;

/**
 * Contoh huta (kampung asal) per cabang dan keluarga dengan istri lebih dari satu (hanya development).
 * Dipanggil dari DemoSilsilahSeeder; aman dijalankan ulang.
 */
class DemoKeluargaSeeder extends Seeder
{
    private const HUTA = [
        'Lumban Motung, Tapanuli Utara (contoh)', 'Siborongborong, Tapanuli Utara (contoh)', 'Sipoholon, Tapanuli Utara (contoh)',
        'Pangaribuan, Tapanuli Utara (contoh)', 'Muara, Tapanuli Utara (contoh)', 'Dolok Sanggul, Humbang Hasundutan (contoh)',
        'Balige, Toba (contoh)', 'Lumban Julu, Toba (contoh)',
    ];

    public function run(): void
    {
        $g1 = $this->db->table('persons')->where('generasi_ke', 1)->where('garis', 'utama')->get()->getRow();
        if ($g1 === null) {
            return;
        }

        // Huta asal leluhur, lalu setiap cabang Sundut 3 mewariskan hutanya ke seluruh keturunan.
        $this->db->table('persons')->where('generasi_ke <=', 2)->update(['huta' => self::HUTA[0]]);
        $cabang = $this->db->table('persons')->select('id')->where('generasi_ke', 3)->whereIn('garis', ['utama', 'boru'])->orderBy('id')->get()->getResultArray();
        foreach ($cabang as $i => $c) {
            $this->db->query(
                'UPDATE persons p JOIN person_paths pp ON pp.descendant_id = p.id SET p.huta = ? WHERE pp.ancestor_id = ? AND p.garis IN (\'utama\', \'boru\')',
                [self::HUTA[$i % count(self::HUTA)], (int) $c['id']],
            );
        }

        // Tiga kepala keluarga beristri dua: anak terakhirnya dari istri kedua.
        $kandidat = $this->db->query(
            "SELECT p.id FROM persons p
             JOIN marriages m ON m.suami_id = p.id AND m.deleted_at IS NULL
             WHERE p.garis = 'utama' AND p.generasi_ke BETWEEN 8 AND 11 AND p.deleted_at IS NULL
               AND (SELECT COUNT(*) FROM persons a WHERE a.induk_id = p.id AND a.deleted_at IS NULL) >= 3
             GROUP BY p.id HAVING COUNT(m.id) = 1
             ORDER BY p.generasi_ke, p.id LIMIT 3",
        )->getResultArray();

        // Sertakan leluhur akun member@ agar contoh terlihat di mapping-nya.
        $member = $this->db->table('users')->select('person_id')->where('username', 'member')->get()->getRow();
        if ($member?->person_id) {
            $leluhur = $this->db->query(
                "SELECT p.id FROM persons p JOIN person_paths pp ON pp.ancestor_id = p.id
                 WHERE pp.descendant_id = ? AND pp.depth BETWEEN 2 AND 3 AND p.garis = 'utama'
                   AND (SELECT COUNT(*) FROM persons a WHERE a.induk_id = p.id AND a.deleted_at IS NULL) >= 2
                   AND (SELECT COUNT(*) FROM marriages m WHERE m.suami_id = p.id AND m.deleted_at IS NULL) = 1
                 ORDER BY pp.depth LIMIT 1",
                [(int) $member->person_id],
            )->getRow();
            if ($leluhur !== null) {
                array_unshift($kandidat, ['id' => $leluhur->id]);
            }
        }

        $service = new PersonService();
        $marga   = ['Simanjuntak', 'Hutagalung', 'Situmorang', 'Nainggolan'];
        $nama    = ['Tiur', 'Rosdiana', 'Lamria', 'Hotnida'];
        foreach (array_slice($kandidat, 0, 4) as $i => $k) {
            $istri2 = $service->tambahPasangan((int) $k['id'], [
                'nama_lengkap' => $nama[$i] . ' br. ' . $marga[$i] . ' (contoh)',
                'marga_nama'   => $marga[$i],
                'status_hidup' => 'meninggal',
            ], ['status' => 'menikah'], null);

            $anak = $this->db->table('persons')->select('id')->where('induk_id', (int) $k['id'])->where('deleted_at', null)
                ->orderBy('urutan_anak', 'DESC')->limit(1)->get()->getResultArray();
            foreach ($anak as $a) {
                $this->db->table('persons')->where('id', (int) $a['id'])->update(['ibu_id' => $istri2->id]);
            }
        }

        echo '  Huta per cabang dan ' . min(4, count($kandidat)) . ' keluarga beristri dua.' . PHP_EOL;
    }
}
