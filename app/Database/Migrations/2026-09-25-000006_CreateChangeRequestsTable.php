<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Usulan tambah/ubah data dari member, menunggu verifikasi.
 */
class CreateChangeRequestsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marga_id'            => ['type' => 'INT', 'unsigned' => true],
            'user_id'             => ['type' => 'INT', 'unsigned' => true],
            'person_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Orang yang diubah, atau induk untuk tambah anak'],
            'jenis'               => ['type' => 'ENUM', 'constraint' => ['tambah_anak', 'tambah_pasangan', 'ubah_data']],
            'payload'             => ['type' => 'JSON'],
            'status'              => ['type' => 'ENUM', 'constraint' => ['pending', 'disetujui', 'ditolak'], 'default' => 'pending'],
            'catatan_pengusul'    => ['type' => 'TEXT', 'null' => true],
            'catatan_verifikator' => ['type' => 'TEXT', 'null' => true],
            'hasil_person_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['marga_id', 'status', 'created_at']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('person_id');
        $this->forge->addForeignKey('marga_id', 'marga', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'persons', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('change_requests');
    }

    public function down(): void
    {
        $this->forge->dropTable('change_requests', true);
    }
}
