<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Punguan awal: dimulai dari Medan. Punguan lain (Jabodetabek, global, dst.)
 * ditambahkan Super Admin lewat menu Admin → Punguan.
 */
class PunguanSeeder extends Seeder
{
    public function run(): void
    {
        $marga = $this->db->table('marga')->where('kode', 'PDS')->get()->getRow();
        if ($marga === null || $this->db->table('punguan')->where('slug', 'medan')->countAllResults() > 0) {
            return;
        }

        $this->db->table('punguan')->insert([
            'marga_id'     => $marga->id,
            'nama'         => 'Punguan Medan',
            'slug'         => 'medan',
            'tingkat'      => 'daerah',
            'wilayah_kode' => '12.71',
            'keterangan'   => 'Punguan pomparan di Kota Medan dan sekitarnya.',
            'is_active'    => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
