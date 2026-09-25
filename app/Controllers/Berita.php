<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BeritaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Berita extends BaseController
{
    public function index(): string
    {
        $kategori = $this->request->getGet('kategori');
        $kategori = is_string($kategori) && isset(config('Silsilah')->kategoriBerita[$kategori]) ? $kategori : null;

        $model = new BeritaModel();

        return view('berita/index', [
            'rows'     => $model->terbit($kategori)->paginate(9),
            'pager'    => $model->pager,
            'kategori' => $kategori,
        ]);
    }

    public function baca(string $slug): string
    {
        $model  = new BeritaModel();
        $berita = $model->terbit()->where('slug', $slug)->first();
        if ($berita === null) {
            throw PageNotFoundException::forPageNotFound('Berita tidak ditemukan.');
        }

        $model->builder()->where('id', $berita['id'])->set('dilihat', 'dilihat + 1', false)->update();

        return view('berita/baca', [
            'b'       => $berita,
            'lainnya' => (new BeritaModel())->terbit()->where('id !=', $berita['id'])->findAll(3),
        ]);
    }
}
