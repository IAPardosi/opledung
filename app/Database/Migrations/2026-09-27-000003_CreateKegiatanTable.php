<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKegiatanTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marga_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'jenis'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'judul'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 220],
            'deskripsi'      => ['type' => 'MEDIUMTEXT', 'null' => true],
            'mulai'          => ['type' => 'DATETIME'],
            'selesai'        => ['type' => 'DATETIME', 'null' => true],
            'lokasi'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'alamat'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kabupaten_kode' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'peta_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'kontak'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'gambar'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'terbit', 'batal'], 'default' => 'draft'],
            'created_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'mulai']);
        $this->forge->addForeignKey('marga_id', 'marga', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('kegiatan');
    }

    public function down(): void
    {
        $this->forge->dropTable('kegiatan', true);
    }
}
