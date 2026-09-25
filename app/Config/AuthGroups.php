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
    public string $defaultGroup = 'member';

    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Akses penuh, termasuk membuat marga baru dan mengelola admin.',
        ],
        'ketua_adat' => [
            'title'       => 'Ketua Adat',
            'description' => 'Menetapkan dan memvalidasi Silsilah Pokok marganya.',
        ],
        'verifikator' => [
            'title'       => 'Admin/Verifikator',
            'description' => 'Memverifikasi usulan data anggota di marganya.',
        ],
        'member' => [
            'title'       => 'Member',
            'description' => 'Mengelola profil sendiri dan mengusulkan data keluarga.',
        ],
    ];

    public array $permissions = [
        'admin.access'       => 'Mengakses halaman admin',
        'marga.manage'       => 'Membuat dan mengubah data marga',
        'users.manage'       => 'Mengelola akun pengguna dan role',
        'silsilah.pokok'     => 'Mengubah dan memvalidasi Silsilah Pokok',
        'silsilah.edit'      => 'Menambah dan mengubah data anggota secara langsung',
        'silsilah.verify'    => 'Menyetujui atau menolak usulan data',
        'silsilah.propose'   => 'Mengusulkan tambah/ubah data keluarga',
        'silsilah.view'      => 'Melihat detail silsilah dan profil anggota',
        'data.sensitive'     => 'Melihat NIK dan No. KK',
    ];

    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'marga.*',
            'users.*',
            'silsilah.*',
            'data.*',
        ],
        'ketua_adat' => [
            'admin.access',
            'silsilah.*',
            'data.sensitive',
        ],
        'verifikator' => [
            'admin.access',
            'silsilah.edit',
            'silsilah.verify',
            'silsilah.propose',
            'silsilah.view',
            'data.sensitive',
        ],
        'member' => [
            'silsilah.propose',
            'silsilah.view',
        ],
    ];
}
