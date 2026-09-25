<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Istilah partuturan (tutur sapa Batak Toba). Kunci dipakai mesin partuturan;
 * sebutan dan keterangan dapat diubah Ketua Adat agar sesuai adat setempat.
 */
class CreatePartuturanTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'kunci'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'sebutan'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kelompok'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'lainnya'],
            'urutan'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('kunci');
        $this->forge->createTable('partuturan');
    }

    public function down(): void
    {
        $this->forge->dropTable('partuturan', true);
    }
}
