<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jenis usulan baru: member mengklaim data silsilah sebagai dirinya ("Ini saya").
 */
class AddKlaimProfilToChangeRequests extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('change_requests', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['tambah_anak', 'tambah_pasangan', 'ubah_data', 'klaim_profil'], 'null' => false],
        ]);
    }

    public function down(): void
    {
        $this->db->table('change_requests')->where('jenis', 'klaim_profil')->delete();
        $this->forge->modifyColumn('change_requests', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['tambah_anak', 'tambah_pasangan', 'ubah_data'], 'null' => false],
        ]);
    }
}
