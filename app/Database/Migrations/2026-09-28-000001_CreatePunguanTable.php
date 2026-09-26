<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Punguan: organisasi pomparan per daerah (Pusat → Daerah → Global).
 * Silsilah tetap satu pohon; punguan hanya lapisan organisasi.
 */
class CreatePunguanTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marga_id'     => ['type' => 'INT', 'unsigned' => true],
            'induk_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nama'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 140],
            'tingkat'      => ['type' => 'ENUM', 'constraint' => ['pusat', 'daerah', 'global']],
            'wilayah_kode' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true, 'comment' => 'Kode provinsi/kab-kota cakupan (daerah Indonesia)'],
            'negara'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'comment' => 'Untuk punguan global'],
            'keterangan'   => ['type' => 'TEXT', 'null' => true],
            'kontak'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['marga_id', 'is_active']);
        $this->forge->addForeignKey('marga_id', 'marga', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('induk_id', 'punguan', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('punguan');
    }

    public function down(): void
    {
        $this->forge->dropTable('punguan', true);
    }
}
