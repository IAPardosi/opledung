<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Huta: kampung asal / bona pasogit keluarga (berbeda dari alamat domisili).
 * Dipakai untuk mengenali orang yang bernama sama di pencarian.
 */
class AddHutaToPersons extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('persons', [
            'huta' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'marga_nama', 'comment' => 'Kampung asal / bona pasogit'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('persons', 'huta');
    }
}
