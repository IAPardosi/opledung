<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Referensi wilayah administrasi Indonesia (kode Kemendagri).
 * Format kode: 12 (provinsi), 12.71 (kab/kota), 12.71.01 (kecamatan), 12.71.01.1001 (desa/kelurahan).
 */
class CreateWilayahTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 13],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'tingkat'    => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'comment' => '1=provinsi, 2=kab/kota, 3=kecamatan, 4=desa/kelurahan'],
            'induk_kode' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('kode');
        $this->forge->addKey(['induk_kode', 'nama']);
        $this->forge->addKey(['tingkat', 'nama']);
        $this->forge->createTable('wilayah');
    }

    public function down(): void
    {
        $this->forge->dropTable('wilayah', true);
    }
}
