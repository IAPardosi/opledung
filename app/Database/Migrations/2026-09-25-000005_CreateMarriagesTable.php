<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarriagesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'suami_id'      => ['type' => 'INT', 'unsigned' => true],
            'istri_id'      => ['type' => 'INT', 'unsigned' => true],
            'urutan'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1, 'comment' => 'Pernikahan ke- (dari sisi anggota marga)'],
            'tanggal_nikah' => ['type' => 'DATE', 'null' => true],
            'tempat_nikah'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['menikah', 'cerai_hidup', 'cerai_mati'], 'default' => 'menikah'],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['suami_id', 'istri_id']);
        $this->forge->addKey('istri_id');
        $this->forge->addForeignKey('suami_id', 'persons', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('istri_id', 'persons', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('marriages');
    }

    public function down(): void
    {
        $this->forge->dropTable('marriages', true);
    }
}
