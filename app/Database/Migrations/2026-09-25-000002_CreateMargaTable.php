<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMargaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'                 => ['type' => 'VARCHAR', 'constraint' => 5, 'comment' => 'Prefiks kode anggota, mis. PDS'],
            'nama'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'nama_rumpun'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'Mis. Op. Ledung'],
            'asal_kampung'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Bona pasogit'],
            'asal_wilayah_kode'    => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'sejarah'              => ['type' => 'TEXT', 'null' => true],
            'batas_silsilah_pokok' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 10, 'comment' => 'Generasi 1 s.d. nilai ini hanya dikelola Ketua Adat'],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->addUniqueKey('nama');
        $this->forge->createTable('marga');
    }

    public function down(): void
    {
        $this->forge->dropTable('marga', true);
    }
}
