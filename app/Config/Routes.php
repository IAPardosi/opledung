<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Publik (tanpa login): pohon umum, daftar generasi, pencarian nama.
$routes->get('/', 'Beranda::index');
$routes->get('silsilah', 'Silsilah::pohon');
$routes->get('silsilah/(:num)', 'Silsilah::pohon/$1');
$routes->get('generasi', 'Silsilah::generasi');
$routes->get('api/pohon/(:num)', 'Api::pohon/$1');
$routes->get('api/cari', 'Api::cari');
$routes->get('api/wilayah', 'Api::wilayah');

service('auth')->routes($routes);

// Wajib login: detail silsilah dan profil per anggota.
$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('profil-saya', 'Anggota::profilSaya');
    $routes->get('usulan-saya', 'Anggota::usulanSaya');

    $routes->get('anggota/(:num)', 'Anggota::profil/$1');
    $routes->get('anggota/(:num)/foto', 'Anggota::foto/$1');
    $routes->match(['GET', 'POST'], 'anggota/(:num)/tambah-anak', 'Anggota::tambahAnak/$1');
    $routes->match(['GET', 'POST'], 'anggota/(:num)/tambah-pasangan', 'Anggota::tambahPasangan/$1');
    $routes->match(['GET', 'POST'], 'anggota/(:num)/ubah', 'Anggota::ubah/$1');
    $routes->post('anggota/(:num)/validasi', 'Anggota::validasi/$1');
    $routes->post('anggota/(:num)/hapus', 'Anggota::hapus/$1');
    $routes->post('anggota/(:num)/klaim', 'Anggota::klaim/$1');
});

// Admin: Ketua Adat, Verifikator, Super Admin.
$routes->group('admin', ['filter' => 'permission:admin.access', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('usulan', 'Usulan::index');
    $routes->get('usulan/(:num)', 'Usulan::detail/$1');
    $routes->post('usulan/(:num)/setujui', 'Usulan::setujui/$1');
    $routes->post('usulan/(:num)/tolak', 'Usulan::tolak/$1');

    $routes->match(['GET', 'POST'], 'import', 'Import::index');
    $routes->post('import/simpan', 'Import::simpan');
    $routes->get('import/template', 'Import::template');

    $routes->match(['GET', 'POST'], 'leluhur-awal', 'LeluhurAwal::index');
});

// Khusus Super Admin.
$routes->group('admin', ['filter' => 'group:superadmin', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('marga', 'Marga::index');
    $routes->match(['GET', 'POST'], 'marga/tambah', 'Marga::form');
    $routes->match(['GET', 'POST'], 'marga/(:num)/ubah', 'Marga::form/$1');

    $routes->get('pengguna', 'Pengguna::index');
    $routes->match(['GET', 'POST'], 'pengguna/(:num)/ubah', 'Pengguna::ubah/$1');
});
