<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BeritaModel;
use App\Services\KontenService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Berita extends BaseController
{
    public function index(): string
    {
        $model = new BeritaModel();
        $this->saringMarga($model);

        return view('admin/berita_index', [
            'rows'  => $model->orderBy('id', 'DESC')->paginate(25),
            'pager' => $model->pager,
        ]);
    }

    public function form(?int $id = null): RedirectResponse|string
    {
        $model  = new BeritaModel();
        $berita = $id === null ? null : $this->ambil($id);

        if ($this->request->is('post')) {
            $status = $this->request->getPost('status') === 'terbit' ? 'terbit' : 'draft';
            $data   = [
                'kategori'   => (string) $this->request->getPost('kategori'),
                'judul'      => trim((string) $this->request->getPost('judul')),
                'ringkasan'  => trim((string) $this->request->getPost('ringkasan')) ?: null,
                'isi'        => trim((string) $this->request->getPost('isi')),
                'status'     => $status,
                'terbit_at'  => $status === 'terbit' ? ($berita['terbit_at'] ?? date('Y-m-d H:i:s')) : null,
                'marga_id'   => $berita['marga_id'] ?? $this->user()->marga_id,
                'updated_by' => $this->user()->id,
            ];

            $konten = new KontenService();
            if ($berita === null || $berita['judul'] !== $data['judul']) {
                $data['slug'] = $konten->slugUnik('berita', $data['judul'] ?: 'berita', $id);
            }

            $gambar = $this->request->getFile('gambar');
            if ($gambar !== null && $gambar->isValid()) {
                if (! $this->validate(['gambar' => ['label' => 'Gambar', 'rules' => 'is_image[gambar]|max_size[gambar,4096]|mime_in[gambar,image/jpeg,image/png,image/webp]']])) {
                    return redirect()->back()->withInput()->with('kesalahan', $this->validator->getErrors());
                }
                $data['gambar'] = $konten->simpanGambar($gambar, $berita['gambar'] ?? null);
            } elseif ($this->request->getPost('hapus_gambar') && $berita !== null) {
                $konten->hapusGambar($berita['gambar']);
                $data['gambar'] = null;
            }

            if ($berita === null) {
                $data['created_by'] = $this->user()->id;
                $ok = $model->insert($data) !== false;
            } else {
                $ok = $model->update($id, $data);
            }
            if (! $ok) {
                return redirect()->back()->withInput()->with('kesalahan', $model->errors());
            }

            return redirect()->to('admin/berita')->with('sukses', $status === 'terbit' ? 'Berita diterbitkan.' : 'Berita disimpan sebagai draf.');
        }

        return view('admin/berita_form', ['b' => $berita]);
    }

    public function hapus(int $id): RedirectResponse
    {
        $this->ambil($id);
        (new BeritaModel())->delete($id);

        return redirect()->to('admin/berita')->with('sukses', 'Berita dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function ambil(int $id): array
    {
        $model = new BeritaModel();
        $this->saringMarga($model);
        $b = $model->find($id);
        if ($b === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $b;
    }

    private function saringMarga(BeritaModel $model): void
    {
        if (! $this->user()->inGroup('superadmin')) {
            $model->groupStart()->where('marga_id', $this->user()->marga_id)->orWhere('marga_id', null)->groupEnd();
        }
    }
}
