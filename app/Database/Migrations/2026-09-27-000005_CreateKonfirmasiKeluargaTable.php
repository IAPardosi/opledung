<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kesaksian keluarga atas usulan (terutama pendaftaran anggota baru):
 * kerabat yang sudah terverifikasi menyatakan data itu benar atau salah.
 */
class CreateKonfirmasiKeluargaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'change_request_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'           => ['type' => 'INT', 'unsigned' => true],
            'person_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'benar'             => ['type' => 'TINYINT', 'constraint' => 1],
            'catatan'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['change_request_id', 'user_id']);
        $this->forge->addForeignKey('change_request_id', 'change_requests', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('konfirmasi_keluarga');
    }

    public function down(): void
    {
        $this->forge->dropTable('konfirmasi_keluarga', true);
    }
}
