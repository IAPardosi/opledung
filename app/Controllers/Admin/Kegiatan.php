<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KegiatanModel;
use App\Services\KontenService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Kegiatan extends BaseController
{
    public function index(): string
    {
        $model = new KegiatanModel();
        $this->saringMarga($model);

        return view('admin/kegiatan_index', [
            'rows'  => $model->orderBy('mulai', 'DESC')->paginate(25),
            'pager' => $model->pager,
        ]);
    }

    public function form(?int $id = null): RedirectResponse|string
    {
        $model    = new KegiatanModel();
        $kegiatan = $id === null ? null : $this->ambil($id);

        if ($this->request->is('post')) {
            $waktu = static fn (?string $v): ?string => $v ? str_replace('T', ' ', substr($v, 0, 16)) . ':00' : null;
            $data  = [
                'jenis'          => (string) $this->request->getPost('jenis'),
                'judul'          => trim((string) $this->request->getPost('judul')),
                'deskripsi'      => trim((string) $this->request->getPost('deskripsi')) ?: null,
                'mulai'          => $waktu($this->request->getPost('mulai')),
                'selesai'        => $waktu($this->request->getPost('selesai')),
                'lokasi'         => trim((string) $this->request->getPost('lokasi')),
                'alamat'         => trim((string) $this->request->getPost('alamat')) ?: null,
                'kabupaten_kode' => $this->request->getPost('kabupaten_kode') ?: null,
                'peta_url'       => trim((string) $this->request->getPost('peta_url')) ?: null,
                'kontak'         => trim((string) $this->request->getPost('kontak')) ?: null,
                'status'         => in_array($this->request->getPost('status'), ['draft', 'terbit', 'batal'], true) ? $this->request->getPost('status') : 'draft',
                'marga_id'       => $kegiatan['marga_id'] ?? $this->user()->marga_id,
                'updated_by'     => $this->user()->id,
            ];
            if (! array_key_exists($data['jenis'], config('Silsilah')->jenisKegiatan)) {
                $data['jenis'] = 'lainnya';
            }

            $konten = new KontenService();
            if ($kegiatan === null || $kegiatan['judul'] !== $data['judul']) {
                $data['slug'] = $konten->slugUnik('kegiatan', $data['judul'] ?: 'kegiatan', $id);
            }

            $gambar = $this->request->getFile('gambar');
            if ($gambar !== null && $gambar->isValid()) {
                if (! $this->validate(['gambar' => ['label' => 'Gambar', 'rules' => 'is_image[gambar]|max_size[gambar,4096]|mime_in[gambar,image/jpeg,image/png,image/webp]']])) {
                    return redirect()->back()->withInput()->with('kesalahan', $this->validator->getErrors());
                }
                $data['gambar'] = $konten->simpanGambar($gambar, $kegiatan['gambar'] ?? null);
            }

            if ($kegiatan === null) {
                $data['created_by'] = $this->user()->id;
                $ok = $model->insert($data) !== false;
            } else {
                $ok = $model->update($id, $data);
            }
            if (! $ok) {
                return redirect()->back()->withInput()->with('kesalahan', $model->errors());
            }

            return redirect()->to('admin/kegiatan')->with('sukses', 'Kegiatan disimpan.');
        }

        return view('admin/kegiatan_form', ['k' => $kegiatan]);
    }

    public function hapus(int $id): RedirectResponse
    {
        $this->ambil($id);
        (new KegiatanModel())->delete($id);

        return redirect()->to('admin/kegiatan')->with('sukses', 'Kegiatan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function ambil(int $id): array
    {
        $model = new KegiatanModel();
        $this->saringMarga($model);
        $k = $model->find($id);
        if ($k === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $k;
    }

    private function saringMarga(KegiatanModel $model): void
    {
        if (! $this->user()->inGroup('superadmin')) {
            $model->groupStart()->where('marga_id', $this->user()->marga_id)->orWhere('marga_id', null)->groupEnd();
        }
    }
}
