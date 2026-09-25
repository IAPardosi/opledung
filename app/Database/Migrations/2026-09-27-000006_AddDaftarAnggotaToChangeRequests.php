<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jenis usulan 'daftar_anggota': pendaftaran member baru beserta silsilahnya.
 * person_id berisi leluhur terdekat yang sudah tercatat.
 */
class AddDaftarAnggotaToChangeRequests extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('change_requests', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['tambah_anak', 'tambah_pasangan', 'ubah_data', 'klaim_profil', 'daftar_anggota'], 'null' => false],
        ]);
        $this->forge->addColumn('change_requests', [
            'kabupaten_kode' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true, 'after' => 'person_id', 'comment' => 'Domisili pengusul, untuk rute ke Admin Wilayah'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('change_requests', 'kabupaten_kode');
        $this->db->table('change_requests')->where('jenis', 'daftar_anggota')->delete();
        $this->forge->modifyColumn('change_requests', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['tambah_anak', 'tambah_pasangan', 'ubah_data', 'klaim_profil'], 'null' => false],
        ]);
    }
}
