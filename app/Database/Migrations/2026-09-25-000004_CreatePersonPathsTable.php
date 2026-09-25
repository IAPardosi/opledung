<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Closure table pohon marga: setiap pasangan leluhur→keturunan beserta jaraknya.
 * Baris depth = 0 adalah orang itu sendiri.
 */
class CreatePersonPathsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'ancestor_id'   => ['type' => 'INT', 'unsigned' => true],
            'descendant_id' => ['type' => 'INT', 'unsigned' => true],
            'depth'         => ['type' => 'SMALLINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['ancestor_id', 'descendant_id']);
        $this->forge->addKey(['descendant_id', 'depth']);
        $this->forge->addKey(['ancestor_id', 'depth']);
        $this->forge->addForeignKey('ancestor_id', 'persons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('descendant_id', 'persons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('person_paths');
    }

    public function down(): void
    {
        $this->forge->dropTable('person_paths', true);
    }
}
