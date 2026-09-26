<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Member Punguan dan keuangan sederhana punguan.
 *
 *  - keanggotaan_punguan : member marga yang resmi menjadi anggota punguan (disahkan Penatua).
 *                          Hanya anggota berstatus 'aktif' yang memiliki kewajiban punguan.
 *  - keuangan_kategori   : jenis catatan keuangan per punguan (Iuran Bulanan, Hamauliateon, Toktok Ripe, dll.)
 *  - keuangan_catatan    : pembayaran per anggota, dicatat Humas lalu divalidasi Penatua.
 */
class CreateKeanggotaanDanKeuanganPunguan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'punguan_id'     => ['type' => 'INT', 'unsigned' => true],
            'person_id'      => ['type' => 'INT', 'unsigned' => true],
            'nomor_anggota'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['menunggu', 'aktif', 'nonaktif', 'ditolak'], 'default' => 'menunggu'],
            'tanggal_masuk'  => ['type' => 'DATE', 'null' => true],
            'tanggal_keluar' => ['type' => 'DATE', 'null' => true],
            'diajukan_oleh'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'disahkan_oleh'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'disahkan_at'    => ['type' => 'DATETIME', 'null' => true],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['punguan_id', 'status'], false, false, 'keanggotaan_punguan_status');
        $this->forge->addKey(['person_id', 'status'], false, false, 'keanggotaan_person_status');
        $this->forge->addForeignKey('punguan_id', 'punguan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'persons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('keanggotaan_punguan');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'punguan_id'      => ['type' => 'INT', 'unsigned' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'jenis'           => ['type' => 'ENUM', 'constraint' => ['bulanan', 'tahunan', 'sekali'], 'default' => 'sekali',
                'comment' => 'bulanan/tahunan: tercatat per periode; sekali: per peristiwa (hamauliateon, toktok ripe, dll.)'],
            'nominal_standar' => ['type' => 'DECIMAL', 'constraint' => '14,0', 'null' => true],
            'keterangan'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'urutan'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['punguan_id', 'is_active']);
        $this->forge->addForeignKey('punguan_id', 'punguan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('keuangan_kategori');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'punguan_id'      => ['type' => 'INT', 'unsigned' => true],
            'keanggotaan_id'  => ['type' => 'INT', 'unsigned' => true],
            'person_id'       => ['type' => 'INT', 'unsigned' => true],
            'kategori_id'     => ['type' => 'INT', 'unsigned' => true],
            'periode'         => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'comment' => 'YYYY-MM (bulanan) atau YYYY (tahunan)'],
            'tanggal'         => ['type' => 'DATE'],
            'nominal'         => ['type' => 'DECIMAL', 'constraint' => '14,0'],
            'metode'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'keterangan'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['menunggu', 'sah', 'ditolak'], 'default' => 'menunggu'],
            'alasan_tolak'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dicatat_oleh'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'divalidasi_oleh' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'divalidasi_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['punguan_id', 'status'], false, false, 'keuangan_punguan_status');
        $this->forge->addKey(['keanggotaan_id', 'kategori_id', 'periode'], false, false, 'keuangan_anggota_periode');
        $this->forge->addForeignKey('punguan_id', 'punguan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('keanggotaan_id', 'keanggotaan_punguan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kategori_id', 'keuangan_kategori', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('keuangan_catatan');
    }

    public function down(): void
    {
        $this->forge->dropTable('keuangan_catatan', true);
        $this->forge->dropTable('keuangan_kategori', true);
        $this->forge->dropTable('keanggotaan_punguan', true);
    }
}
