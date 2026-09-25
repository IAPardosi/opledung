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
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON([]);
        }

        $rows = (new PersonModel())
            ->daftar((int) $this->margaAktif()['id'], null, null, $q)
            ->asArray()
            ->findAll(15);

        return $this->response->setJSON(array_map(static fn (array $r): array => [
            'id'           => (int) $r['id'],
            'kode_anggota' => $r['kode_anggota'],
            'nama_lengkap' => $r['nama_lengkap'],
            'generasi_ke'  => (int) $r['generasi_ke'],
            'garis'        => $r['garis'],
            'nama_induk'   => $r['nama_induk'],
        ], $rows));
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
