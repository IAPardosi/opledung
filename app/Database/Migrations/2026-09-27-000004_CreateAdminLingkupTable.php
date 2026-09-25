<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Lingkup kerja Admin Wilayah:
 *  - jenis 'wilayah': kode kab/kota (atau provinsi) domisili anggota yang ditangani
 *  - jenis 'cabang' : ID leluhur; seluruh keturunannya menjadi tanggung jawab admin ini
 */
class CreateAdminLingkupTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'jenis'      => ['type' => 'ENUM', 'constraint' => ['wilayah', 'cabang']],
            'nilai'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'jenis', 'nilai']);
        $this->forge->addKey(['jenis', 'nilai']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('admin_lingkup');
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_lingkup', true);
    }
}
