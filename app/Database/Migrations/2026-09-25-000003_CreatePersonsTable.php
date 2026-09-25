<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Semua orang dalam silsilah.
 *
 * garis:
 *  - utama     : anak laki-laki penerus marga (garis diteruskan)
 *  - boru      : anak perempuan marga (dicatat beserta suami dan anaknya)
 *  - anak_boru : anak dari boru (ujung cabang, tidak diteruskan)
 *  - pasangan  : istri/suami dari marga lain
 *
 * induk_id adalah orang tua dalam pohon marga: ayah untuk garis utama/boru,
 * ibu (boru) untuk anak_boru, NULL untuk Generasi 1 dan pasangan.
 */
class CreatePersonsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marga_id'            => ['type' => 'INT', 'unsigned' => true],
            'kode_anggota'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'nomor_urut'          => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Nomor urut per marga'],
            'generasi_ke'         => ['type' => 'SMALLINT', 'unsigned' => true],
            'garis'               => ['type' => 'ENUM', 'constraint' => ['utama', 'boru', 'anak_boru', 'pasangan']],
            'induk_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ayah_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ibu_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nama_ibu'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'Diisi bila data ibu belum ada di sistem'],
            'urutan_anak'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'marga_nama'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'Marga asal untuk pasangan dan anak_boru'],

            // Data pribadi
            'nama_lengkap'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'nama_panggilan'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gelar_adat'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'jenis_kelamin'       => ['type' => 'ENUM', 'constraint' => ['L', 'P']],
            'tempat_lahir'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tanggal_lahir'       => ['type' => 'DATE', 'null' => true],
            'tahun_lahir'         => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'Dipakai bila hanya tahun yang diketahui'],
            'agama'               => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status_perkawinan'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pendidikan_terakhir' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'pekerjaan'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'golongan_darah'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'kewarganegaraan'     => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'WNI'],
            'nik_enc'             => ['type' => 'TEXT', 'null' => true, 'comment' => 'NIK terenkripsi'],
            'nik_hash'            => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'comment' => 'HMAC NIK untuk cek duplikat'],
            'no_kk_enc'           => ['type' => 'TEXT', 'null' => true, 'comment' => 'No. KK terenkripsi'],

            // Alamat & kontak
            'alamat_jalan'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'rt'                  => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            'rw'                  => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            'desa_kode'           => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'kecamatan_kode'      => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'kabupaten_kode'      => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'provinsi_kode'       => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'kode_pos'            => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'no_hp'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'sembunyikan_kontak'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],

            // Status hidup
            'status_hidup'        => ['type' => 'ENUM', 'constraint' => ['hidup', 'meninggal', 'tidak_diketahui'], 'default' => 'hidup'],
            'tanggal_wafat'       => ['type' => 'DATE', 'null' => true],
            'tahun_wafat'         => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'tempat_makam'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],

            // Profil
            'foto'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'biografi'            => ['type' => 'TEXT', 'null' => true],

            // Validasi & jejak
            'status_data'         => ['type' => 'ENUM', 'constraint' => ['draft', 'terverifikasi', 'terkunci'], 'default' => 'draft'],
            'verified_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode_anggota');
        $this->forge->addUniqueKey(['marga_id', 'nomor_urut']);
        $this->forge->addKey(['marga_id', 'generasi_ke', 'garis']);
        $this->forge->addKey(['induk_id', 'urutan_anak']);
        $this->forge->addKey('ayah_id');
        $this->forge->addKey('ibu_id');
        $this->forge->addKey('nama_lengkap');
        $this->forge->addKey('nik_hash');
        $this->forge->addKey('kabupaten_kode');
        $this->forge->addForeignKey('marga_id', 'marga', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('induk_id', 'persons', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('ayah_id', 'persons', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('ibu_id', 'persons', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('persons');

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE persons ADD FULLTEXT INDEX persons_nama_fulltext (nama_lengkap, nama_panggilan, gelar_adat)');
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('persons', true);
    }
}
