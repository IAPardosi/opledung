<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Models\MargaModel;
use App\Services\ImportService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Import Excel/CSV: unggah → pratinjau (tidak disimpan) → simpan.
 */
class Import extends BaseController
{
    private const SESI = 'import_file';

    public function index(): RedirectResponse|string
    {
        $user   = $this->user();
        $margas = $user->inGroup('superadmin')
            ? (new MargaModel())->where('is_active', 1)->findAll()
            : (new MargaModel())->where('id', $user->marga_id)->findAll();

        if ($this->request->is('get')) {
            return view('admin/import', ['margas' => $margas, 'hasil' => null]);
        }

        $margaId = (int) $this->request->getPost('marga_id');
        if (! in_array($margaId, array_map('intval', array_column($margas, 'id')), true)) {
            return redirect()->back()->with('galat', 'Pilih marga tujuan.');
        }

        $aturan = ['berkas' => [
            'label' => 'Berkas',
            'rules' => 'uploaded[berkas]|max_size[berkas,10240]|ext_in[berkas,xlsx,xls,csv]',
        ]];
        if (! $this->validate($aturan)) {
            return redirect()->back()->with('kesalahan', $this->validator->getErrors());
        }

        $file = $this->request->getFile('berkas');
        $dir  = WRITEPATH . 'uploads/import/';
        $nama = bin2hex(random_bytes(12)) . '.' . strtolower($file->getClientExtension());
        $file->move($dir, $nama);

        $this->hapusBerkasLama();
        session()->set(self::SESI, ['path' => $dir . $nama, 'nama' => $file->getClientName(), 'marga_id' => $margaId]);

        return $this->jalankan(false, $margas);
    }

    public function simpan(): RedirectResponse|string
    {
        $user   = $this->user();
        $margas = $user->inGroup('superadmin')
            ? (new MargaModel())->where('is_active', 1)->findAll()
            : (new MargaModel())->where('id', $user->marga_id)->findAll();

        return $this->jalankan(true, $margas);
    }

    public function template(): DownloadResponse
    {
        return $this->response->download(ROOTPATH . 'docs/template-import-anggota.csv', null)->setFileName('template-import-anggota.csv');
    }

    /**
     * @param list<array<string, mixed>> $margas
     */
    private function jalankan(bool $simpan, array $margas): RedirectResponse|string
    {
        $sesi = session(self::SESI);
        if (! is_array($sesi) || ! is_file($sesi['path'])) {
            return redirect()->to('admin/import')->with('galat', 'Berkas import tidak ditemukan. Silakan unggah ulang.');
        }

        set_time_limit(0);
        $service = new ImportService();

        try {
            $rows  = $service->bacaFile($sesi['path'], $sesi['nama']);
            $hasil = $service->proses((int) $sesi['marga_id'], $rows, $this->user(), $simpan);
        } catch (SilsilahException $e) {
            return redirect()->to('admin/import')->with('galat', $e->getMessage());
        }

        if ($hasil['disimpan']) {
            $this->hapusBerkasLama();

            return redirect()->to('admin/import')->with('sukses', "Import selesai: {$hasil['jumlah_ok']} baris tersimpan.");
        }

        return view('admin/import', [
            'margas'   => $margas,
            'hasil'    => $hasil,
            'namaFile' => $sesi['nama'],
            'margaId'  => $sesi['marga_id'],
        ]);
    }

    private function hapusBerkasLama(): void
    {
        $lama = session(self::SESI);
        if (is_array($lama) && is_file($lama['path'])) {
            unlink($lama['path']);
        }
        session()->remove(self::SESI);
    }
}
