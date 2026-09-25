<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBeritaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marga_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = untuk semua marga'],
            'kategori'   => ['type' => 'ENUM', 'constraint' => ['berita', 'pengumuman', 'sukacita', 'dukacita']],
            'judul'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 220],
            'ringkasan'  => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'isi'        => ['type' => 'MEDIUMTEXT'],
            'gambar'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'     => ['type' => 'ENUM', 'constraint' => ['draft', 'terbit'], 'default' => 'draft'],
            'terbit_at'  => ['type' => 'DATETIME', 'null' => true],
            'dilihat'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'terbit_at']);
        $this->forge->addKey(['kategori', 'status', 'terbit_at']);
        $this->forge->addForeignKey('marga_id', 'marga', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('berita');
    }

    public function down(): void
    {
        $this->forge->dropTable('berita', true);
    }
}
