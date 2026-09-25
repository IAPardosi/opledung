<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\KegiatanModel;
use App\Models\WilayahModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Kegiatan extends BaseController
{
    public function index(): string
    {
        return view('kegiatan/index', [
            'akanDatang' => (new KegiatanModel())->akanDatang()->findAll(30),
            'lewat'      => (new KegiatanModel())->sudahLewat()->findAll(12),
        ]);
    }

    public function detail(string $slug): string
    {
        $k = (new KegiatanModel())->whereIn('status', ['terbit', 'batal'])->where('slug', $slug)->first();
        if ($k === null) {
            throw PageNotFoundException::forPageNotFound('Kegiatan tidak ditemukan.');
        }

        $wilayah = $k['kabupaten_kode'] ? (new WilayahModel())->find($k['kabupaten_kode']) : null;

        return view('kegiatan/detail', ['k' => $k, 'wilayah' => $wilayah]);
    }
}
