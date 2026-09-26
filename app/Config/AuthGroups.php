<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * Role pengguna sesuai docs/STANDAR.md bagian 4.
 *
 * Role Ketua Adat dan Verifikator berlaku untuk marga yang tercatat
 * di kolom users.marga_id; Super Admin berlaku untuk semua marga.
 */
class AuthGroups extends ShieldAuthGroups
{
    /**
     * Akun baru berstatus "calon" sampai silsilahnya divalidasi.
     */
    public string $defaultGroup = 'calon';

    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Akses penuh, termasuk membuat marga baru dan mengelola admin.',
        ],
        'ketua_adat' => [
            'title'       => 'Ketua Adat',
            'description' => 'Menetapkan dan memvalidasi Silsilah Pokok, serta istilah partuturan.',
        ],
        'verifikator' => [
            'title'       => 'Admin Marga',
            'description' => 'Memverifikasi usulan dan pendaftaran di seluruh marganya.',
        ],
        'penatua' => [
            'title'       => 'Penatua Punguan',
            'description' => 'Ketua/penatua punguan daerah (mis. Medan): mengesahkan pendaftaran keluarga, member punguan, dan catatan keuangan di punguannya.',
        ],
        'admin_wilayah' => [
            'title'       => 'Admin Wilayah',
            'description' => 'Memverifikasi pendaftaran dan usulan di wilayah atau cabang (pomparan) tertentu.',
        ],
        'humas' => [
            'title'       => 'Humas Punguan',
            'description' => 'Mengelola berita dan kegiatan, mendaftarkan member punguan, dan mencatat keuangan punguannya.',
        ],
        'member' => [
            'title'       => 'Member',
            'description' => 'Anggota terverifikasi: melihat silsilah dan mengusulkan data keluarga.',
        ],
        'calon' => [
            'title'       => 'Calon Member',
            'description' => 'Baru mendaftar; menunggu validasi silsilah.',
        ],
    ];

    public array $permissions = [
        'admin.access'       => 'Mengakses halaman admin',
        'marga.manage'       => 'Membuat dan mengubah data marga',
        'users.manage'       => 'Mengelola akun pengguna dan role',
        'silsilah.pokok'     => 'Mengubah dan memvalidasi Silsilah Pokok',
        'silsilah.edit'      => 'Menambah dan mengubah data anggota secara langsung',
        'silsilah.verify'    => 'Menyetujui atau menolak usulan dan pendaftaran',
        'silsilah.propose'   => 'Mengusulkan tambah/ubah data keluarga',
        'silsilah.view'      => 'Melihat detail silsilah dan profil anggota',
        'data.sensitive'     => 'Melihat NIK dan No. KK',
        'partuturan.kelola'  => 'Mengubah istilah partuturan',
        'konten.kelola'      => 'Mengelola berita dan kegiatan',
        'anggota.kelola'     => 'Mengontrol status anggota (hidup/meninggal, dll.)',
        'punguan.anggota'    => 'Mendaftarkan member punguan',
        'punguan.sahkan'     => 'Mengesahkan dan menonaktifkan member punguan',
        'keuangan.catat'     => 'Mencatat keuangan member punguan dan mengatur kategorinya',
        'keuangan.validasi'  => 'Memvalidasi catatan keuangan punguan',
    ];

    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'marga.*',
            'users.*',
            'silsilah.*',
            'data.*',
            'partuturan.*',
            'konten.*',
            'anggota.*',
            'punguan.*',
            'keuangan.*',
        ],
        'ketua_adat' => [
            'admin.access',
            'silsilah.*',
            'data.sensitive',
            'partuturan.kelola',
            'konten.kelola',
            'anggota.kelola',
        ],
        'verifikator' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
            'konten.kelola',
            'anggota.kelola',
        ],
        // Hak edit/verifikasi penatua dan admin wilayah dibatasi lingkupnya oleh App\Services\LingkupAdmin.
        'penatua' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
            'konten.kelola',
            'anggota.kelola',
            'punguan.anggota',
            'punguan.sahkan',
            'keuangan.catat',
            'keuangan.validasi',
        ],
        'admin_wilayah' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
            'anggota.kelola',
        ],
        // Humas mencatat; Penatua memvalidasi. Lingkupnya punguan akun (users.punguan_id).
        'humas' => [
            'admin.access',
            'konten.kelola',
            'silsilah.propose',
            'silsilah.view',
            'punguan.anggota',
            'keuangan.catat',
        ],
        'member' => [
            'silsilah.propose',
            'silsilah.view',
        ],
        'calon' => [],
    ];
}
