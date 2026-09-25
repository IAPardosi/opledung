<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\MargaModel;
use CodeIgniter\Database\Seeder;

/**
 * Marga awal sistem. Marga lain ditambahkan Super Admin melalui aplikasi.
 */
class MargaSeeder extends Seeder
{
    public function run(): void
    {
        $model = new MargaModel();
        if ($model->where('kode', 'PDS')->first() !== null) {
            return;
        }

        $model->insert([
            'kode'                 => 'PDS',
            'nama'                 => 'Pardosi',
            'nama_rumpun'          => 'Op. Ledung',
            'batas_silsilah_pokok' => 10,
            'is_active'            => 1,
        ]);
    }
}
