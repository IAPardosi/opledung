<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Services\PunguanService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Member Punguan: Humas mendaftarkan, Penatua mengesahkan / menonaktifkan.
 */
class PunguanAnggota extends BaseController
{
    use PilihPunguan;

    private PunguanService $service;

    public function __construct()
    {
        $this->service = new PunguanService();
    }

    public function index(): string
    {
        $daftar  = $this->service->daftarPunguan($this->user(), (int) $this->margaAktif()['id']);
        $punguan = $this->pilihPunguan($daftar);
        $status  = $this->request->getGet('status');
        $status  = in_array($status, ['menunggu', 'aktif', 'nonaktif', 'ditolak'], true) ? $status : null;
        $cari    = trim((string) $this->request->getGet('q'));

        return view('admin/punguan_anggota', [
            'daftar'  => $daftar,
            'punguan' => $punguan,
            'status'  => $status,
            'cari'    => $cari,
            'rows'    => $punguan ? $this->service->daftar((int) $punguan['id'], $status, $cari) : [],
            'rekap'   => $punguan ? $this->service->rekap((int) $punguan['id']) : [],
            'sahkan'  => $this->user()->can('punguan.sahkan'),
        ]);
    }

    public function tambah(): RedirectResponse
    {
        $punguanId = (int) $this->request->getPost('punguan_id');
        try {
            $id = $this->service->ajukan(
                $this->user(),
                $punguanId,
                (int) $this->request->getPost('person_id'),
                (string) $this->request->getPost('catatan'),
                $this->request->getPost('tanggal_masuk') ?: null,
            );
            $k = $this->service->ambil($id);
            $pesan = $k['status'] === 'aktif'
                ? $k['nama_lengkap'] . ' sekarang Member Punguan ' . $k['nama_punguan'] . ' (' . $k['nomor_anggota'] . ').'
                : $k['nama_lengkap'] . ' diajukan menjadi Member Punguan dan menunggu pengesahan Penatua.';

            return redirect()->to('admin/punguan-anggota?punguan=' . $punguanId)->with('sukses', $pesan);
        } catch (SilsilahException $e) {
            return redirect()->to('admin/punguan-anggota?punguan=' . $punguanId)->with('galat', $e->getMessage());
        }
    }

    public function sahkan(int $id): RedirectResponse
    {
        return $this->jalankan($id, fn () => $this->service->sahkan(
            $id,
            $this->user(),
            $this->request->getPost('aksi') === 'setuju',
            (string) $this->request->getPost('catatan'),
            $this->request->getPost('tanggal_masuk') ?: null,
        ), $this->request->getPost('aksi') === 'setuju' ? 'Keanggotaan disahkan.' : 'Pengajuan ditolak.');
    }

    public function nonaktifkan(int $id): RedirectResponse
    {
        return $this->jalankan($id, fn () => $this->service->nonaktifkan(
            $id,
            $this->user(),
            (string) $this->request->getPost('alasan'),
            $this->request->getPost('tanggal_keluar') ?: null,
        ), 'Keanggotaan dinonaktifkan. Riwayat keuangan tetap tersimpan.');
    }

    public function aktifkan(int $id): RedirectResponse
    {
        return $this->jalankan($id, fn () => $this->service->aktifkanKembali($id, $this->user()), 'Keanggotaan diaktifkan kembali.');
    }

    private function jalankan(int $id, callable $aksi, string $pesan): RedirectResponse
    {
        try {
            $punguanId = (int) $this->service->ambil($id)['punguan_id'];
            $aksi();

            return redirect()->to('admin/punguan-anggota?punguan=' . $punguanId)->with('sukses', $pesan);
        } catch (SilsilahException $e) {
            return redirect()->back()->with('galat', $e->getMessage());
        }
    }
}
