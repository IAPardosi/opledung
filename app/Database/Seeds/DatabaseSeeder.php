<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder wajib untuk semua lingkungan (termasuk produksi):
 * php spark db:seed DatabaseSeeder
 *
 * Data contoh silsilah (hanya development): php spark db:seed DemoSilsilahSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WilayahSeeder::class);
        $this->call(MargaSeeder::class);
        $this->call(PartuturanSeeder::class);
        $this->call(PunguanSeeder::class);
        $this->call(SuperAdminSeeder::class);
    }
}
