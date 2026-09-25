<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menautkan akun ke marga (lingkup Ketua Adat/Verifikator/Member)
 * dan ke data dirinya di silsilah.
 */
class AddMargaAndPersonToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'marga_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'username'],
            'person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'marga_id'],
        ]);
        $this->forge->addKey('marga_id');
        $this->forge->addUniqueKey('person_id');
        $this->forge->processIndexes('users');
        $this->db->query('ALTER TABLE users ADD CONSTRAINT users_marga_id_foreign FOREIGN KEY (marga_id) REFERENCES marga(id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->db->query('ALTER TABLE users ADD CONSTRAINT users_person_id_foreign FOREIGN KEY (person_id) REFERENCES persons(id) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('users', 'users_marga_id_foreign');
        $this->forge->dropForeignKey('users', 'users_person_id_foreign');
        $this->forge->dropColumn('users', ['marga_id', 'person_id']);
    }
}
