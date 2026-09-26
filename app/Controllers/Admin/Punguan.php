<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PunguanModel;
use App\Services\AuditLogger;
use App\Services\KontenService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Super Admin mengelola punguan (Pusat, Daerah, Global).
 */
class Punguan extends BaseController
{
    public function index(): string
    {
        return view('admin/punguan_index', ['rows' => (new PunguanModel())->orderBy('tingkat')->orderBy('nama')->findAll()]);
    }

    public function form(?int $id = null): RedirectResponse|string
    {
        $model   = new PunguanModel();
        $punguan = $id === null ? null : $model->find($id);
        if ($id !== null && $punguan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->request->is('post')) {
            $data = [
                'marga_id'     => $punguan['marga_id'] ?? (int) $this->margaAktif()['id'],
                'induk_id'     => $this->request->getPost('induk_id') ?: null,
                'nama'         => trim((string) $this->request->getPost('nama')),
                'tingkat'      => (string) $this->request->getPost('tingkat'),
                'wilayah_kode' => $this->request->getPost('wilayah_kode') ?: null,
                'negara'       => trim((string) $this->request->getPost('negara')) ?: null,
                'keterangan'   => trim((string) $this->request->getPost('keterangan')) ?: null,
                'kontak'       => trim((string) $this->request->getPost('kontak')) ?: null,
                'is_active'    => $this->request->getPost('is_active') ? 1 : 0,
            ];
            if ($punguan === null || $punguan['nama'] !== $data['nama']) {
                $data['slug'] = (new KontenService())->slugUnik('punguan', preg_replace('/^punguan\s+/i', '', $data['nama']) ?: 'punguan', $id);
            }

            $ok = $punguan === null ? $model->insert($data) !== false : $model->update($id, $data);
            if (! $ok) {
                return redirect()->back()->withInput()->with('kesalahan', $model->errors());
            }
            (new AuditLogger())->catat($punguan === null ? 'tambah' : 'ubah', 'punguan', $id ?? (int) $model->getInsertID(), $punguan, $data, $this->user()->id);

            return redirect()->to('admin/punguan')->with('sukses', 'Punguan disimpan.');
        }

        return view('admin/punguan_form', ['p' => $punguan, 'semua' => $model->where('id !=', $id ?? 0)->findAll()]);
    }
}
