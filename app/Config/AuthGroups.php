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
        'admin_wilayah' => [
            'title'       => 'Admin Wilayah',
            'description' => 'Memverifikasi pendaftaran dan usulan di wilayah atau cabang (pomparan) tertentu.',
        ],
        'humas' => [
            'title'       => 'Pengurus Informasi',
            'description' => 'Mengelola berita dan kegiatan.',
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
        ],
        'ketua_adat' => [
            'admin.access',
            'silsilah.*',
            'data.sensitive',
            'partuturan.kelola',
            'konten.kelola',
        ],
        'verifikator' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
            'konten.kelola',
        ],
        // Hak edit/verifikasi admin wilayah dibatasi lingkupnya oleh App\Services\LingkupAdmin.
        'admin_wilayah' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
        ],
        'humas' => [
            'admin.access',
            'konten.kelola',
            'silsilah.propose',
            'silsilah.view',
        ],
        'member' => [
            'silsilah.propose',
            'silsilah.view',
        ],
        'calon' => [],
    ];
}
