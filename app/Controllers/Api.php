<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonModel;
use App\Models\WilayahModel;
use App\Services\SilsilahQuery;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Endpoint JSON publik. Hanya mengembalikan kolom publik (docs/STANDAR.md bagian 5).
 */
class Api extends BaseController
{
    public function pohon(int $id): ResponseInterface
    {
        $kedalaman = max(1, min(5, (int) ($this->request->getGet('kedalaman') ?? 2)));
        $pohon     = (new SilsilahQuery())->pohon($id, $kedalaman, true);

        if ($pohon === null || $pohon['garis'] === 'pasangan') {
            return $this->response->setStatusCode(404)->setJSON(['pesan' => 'Anggota tidak ditemukan.']);
        }

        return $this->response->setJSON($pohon);
    }

    public function cari(): ResponseInterface
    {
        $user    = auth()->loggedIn() ? auth()->user() : null;
        $lengkap = $user !== null && $user->can('silsilah.view');

        return $this->response->setJSON((new \App\Services\PencarianService())->saran(
            (int) $this->margaAktif()['id'],
            (string) $this->request->getGet('q'),
            $lengkap,
        ));
    }

    public function wilayah(): ResponseInterface
    {
        // Semua kab/kota beserta provinsinya (untuk satu pilihan berkelompok).
        if ($this->request->getGet('tingkat') === '2') {
            $rows = (new WilayahModel())
                ->select('wilayah.kode, wilayah.nama, prov.nama AS provinsi')
                ->join('wilayah prov', 'prov.kode = wilayah.induk_kode')
                ->where('wilayah.tingkat', WilayahModel::KABUPATEN)
                ->orderBy('prov.nama')->orderBy('wilayah.nama')
                ->findAll();

            return $this->response->setHeader('Cache-Control', 'public, max-age=86400')->setJSON($rows);
        }

        $induk = $this->request->getGet('induk');
        $induk = is_string($induk) && preg_match('/^[0-9.]{2,13}$/', $induk) ? $induk : null;

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setJSON((new WilayahModel())->turunan($induk));
    }
}
