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
$routes->get('partuturan', 'Partuturan::kamus');
$routes->get('berita', 'Berita::index');
$routes->get('berita/(:segment)', 'Berita::baca/$1');
$routes->get('kegiatan', 'Kegiatan::index');
$routes->get('kegiatan/(:segment)', 'Kegiatan::detail/$1');

service('auth')->routes($routes);

// Login (termasuk calon member): pendaftaran silsilah.
$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->match(['GET', 'POST'], 'pendaftaran', 'Pendaftaran::index');
    $routes->post('pendaftaran/klaim/(:num)', 'Pendaftaran::klaim/$1');
    $routes->get('profil-saya', 'Anggota::profilSaya');
    $routes->get('usulan-saya', 'Anggota::usulanSaya');
});

// Member terverifikasi: detail silsilah, profil per anggota, dan partuturan.
$routes->group('', ['filter' => 'permission:silsilah.view'], static function (RouteCollection $routes): void {
    $routes->get('hubungan', 'Partuturan::hubungan');
    $routes->get('api/partuturan/(:num)', 'Partuturan::api/$1');
    $routes->get('konfirmasi-keluarga', 'Pendaftaran::konfirmasi');
    $routes->post('konfirmasi-keluarga/(:num)', 'Pendaftaran::simpanKonfirmasi/$1');

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

// Istilah partuturan: Ketua Adat.
$routes->group('admin', ['filter' => 'permission:partuturan.kelola', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->match(['GET', 'POST'], 'partuturan', 'Partuturan::index');
});

// Berita & kegiatan: pengurus informasi.
$routes->group('admin', ['filter' => 'permission:konten.kelola', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('berita', 'Berita::index');
    $routes->match(['GET', 'POST'], 'berita/tambah', 'Berita::form');
    $routes->match(['GET', 'POST'], 'berita/(:num)/ubah', 'Berita::form/$1');
    $routes->post('berita/(:num)/hapus', 'Berita::hapus/$1');
    $routes->get('kegiatan', 'Kegiatan::index');
    $routes->match(['GET', 'POST'], 'kegiatan/tambah', 'Kegiatan::form');
    $routes->match(['GET', 'POST'], 'kegiatan/(:num)/ubah', 'Kegiatan::form/$1');
    $routes->post('kegiatan/(:num)/hapus', 'Kegiatan::hapus/$1');
});

// Khusus Super Admin.
$routes->group('admin', ['filter' => 'group:superadmin', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('marga', 'Marga::index');
    $routes->match(['GET', 'POST'], 'marga/tambah', 'Marga::form');
    $routes->match(['GET', 'POST'], 'marga/(:num)/ubah', 'Marga::form/$1');

    $routes->get('pengguna', 'Pengguna::index');
    $routes->match(['GET', 'POST'], 'pengguna/(:num)/ubah', 'Pengguna::ubah/$1');
});
