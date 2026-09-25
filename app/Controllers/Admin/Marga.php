<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MargaModel;
use App\Services\AuditLogger;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Super Admin mengelola marga. Kode marga dikunci setelah ada anggota,
 * karena kode itu menjadi bagian dari kode anggota.
 */
class Marga extends BaseController
{
    public function index(): string
    {
        $rows = db_connect()->table('marga m')
            ->select('m.*, COUNT(p.id) AS jumlah')
            ->join('persons p', "p.marga_id = m.id AND p.deleted_at IS NULL AND p.garis IN ('utama','boru')", 'left')
            ->where('m.deleted_at', null)
            ->groupBy('m.id')
            ->orderBy('m.nama')
            ->get()->getResultArray();

        return view('admin/marga_index', ['rows' => $rows]);
    }

    public function form(?int $id = null): RedirectResponse|string
    {
        $model = new MargaModel();
        $marga = $id === null ? null : $model->find($id);
        if ($id !== null && $marga === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $punyaAnggota = $id !== null && db_connect()->table('persons')->where('marga_id', $id)->countAllResults() > 0;

        if ($this->request->is('post')) {
            $data = [
                'id'                   => $id,
                'kode'                 => $punyaAnggota ? $marga['kode'] : strtoupper(trim((string) $this->request->getPost('kode'))),
                'nama'                 => trim((string) $this->request->getPost('nama')),
                'nama_rumpun'          => trim((string) $this->request->getPost('nama_rumpun')) ?: null,
                'asal_kampung'         => trim((string) $this->request->getPost('asal_kampung')) ?: null,
                'sejarah'              => trim((string) $this->request->getPost('sejarah')) ?: null,
                'batas_silsilah_pokok' => (int) $this->request->getPost('batas_silsilah_pokok'),
                'is_active'            => $this->request->getPost('is_active') ? 1 : 0,
            ];

            if (! $model->save($data)) {
                return redirect()->back()->withInput()->with('kesalahan', $model->errors());
            }

            $recordId = $id ?? (int) $model->getInsertID();
            (new AuditLogger())->catat($id === null ? 'tambah' : 'ubah', 'marga', $recordId, $marga, $data, $this->user()->id);

            return redirect()->to('admin/marga')->with('sukses', 'Data marga disimpan.');
        }

        return view('admin/marga_form', ['marga' => $marga, 'kunciKode' => $punyaAnggota]);
    }
}
