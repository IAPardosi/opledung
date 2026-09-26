<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Validasi dua lapis:
 *  1. Validasi keluarga oleh member sah yang ditunjuk pengusul (orang tua/ompung atau anak/pahompu).
 *  2. Pengesahan oleh penatua punguan (atau Ketua Adat untuk Silsilah Pokok).
 */
class AddPunguanDanValidasiKeluarga extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'punguan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'person_id'],
        ]);
        $this->db->query('ALTER TABLE users ADD CONSTRAINT users_punguan_id_foreign FOREIGN KEY (punguan_id) REFERENCES punguan(id) ON UPDATE CASCADE ON DELETE SET NULL');

        $this->forge->addColumn('change_requests', [
            'punguan_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'kabupaten_kode'],
            'validator_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'punguan_id'],
            'status_keluarga'   => ['type' => 'ENUM', 'constraint' => ['menunggu', 'benar', 'salah', 'tidak_ada'], 'null' => true, 'after' => 'validator_user_id'],
            'catatan_keluarga'  => ['type' => 'TEXT', 'null' => true, 'after' => 'status_keluarga'],
            'keluarga_at'       => ['type' => 'DATETIME', 'null' => true, 'after' => 'catatan_keluarga'],
        ]);
        $this->db->query('ALTER TABLE change_requests ADD INDEX change_requests_validator (validator_user_id, status_keluarga)');
        $this->db->query('ALTER TABLE change_requests ADD INDEX change_requests_punguan (punguan_id, status)');

        foreach (['berita', 'kegiatan'] as $tabel) {
            $this->forge->addColumn($tabel, [
                'punguan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'marga_id', 'comment' => 'NULL = seluruh marga'],
            ]);
        }

        $this->forge->modifyColumn('admin_lingkup', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['wilayah', 'cabang', 'punguan'], 'null' => false],
        ]);

        // Leluhur sebelum marga (informasi sejarah, di luar hitungan sundut): JSON [{nama, keterangan}]
        $this->forge->addColumn('marga', [
            'pra_marga' => ['type' => 'TEXT', 'null' => true, 'after' => 'sejarah'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('marga', 'pra_marga');
        $this->db->table('admin_lingkup')->where('jenis', 'punguan')->delete();
        $this->forge->modifyColumn('admin_lingkup', [
            'jenis' => ['type' => 'ENUM', 'constraint' => ['wilayah', 'cabang'], 'null' => false],
        ]);
        foreach (['berita', 'kegiatan'] as $tabel) {
            $this->forge->dropColumn($tabel, 'punguan_id');
        }
        $this->db->query('ALTER TABLE change_requests DROP INDEX change_requests_validator, DROP INDEX change_requests_punguan');
        $this->forge->dropColumn('change_requests', ['punguan_id', 'validator_user_id', 'status_keluarga', 'catatan_keluarga', 'keluarga_at']);
        $this->forge->dropForeignKey('users', 'users_punguan_id_foreign');
        $this->forge->dropColumn('users', 'punguan_id');
    }
}
