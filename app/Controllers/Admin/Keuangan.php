<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\KeuanganCatatanModel;
use App\Models\KeuanganKategoriModel;
use App\Services\KeuanganService;
use App\Services\PunguanService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Keuangan punguan: Humas mencatat, Penatua memvalidasi. Hanya untuk Member Punguan aktif.
 */
class Keuangan extends BaseController
{
    use PilihPunguan;

    private KeuanganService $keuangan;
    private PunguanService $punguan;

    public function __construct()
    {
        $this->keuangan = new KeuanganService();
        $this->punguan  = new PunguanService();
    }

    public function index(): string
    {
        $daftar  = $this->punguan->daftarPunguan($this->user(), (int) $this->margaAktif()['id']);
        $punguan = $this->pilihPunguan($daftar);
        $tahun   = (int) ($this->request->getGet('tahun') ?: date('Y'));
        $tahun   = max(2000, min((int) date('Y') + 1, $tahun));

        $data = ['daftar' => $daftar, 'punguan' => $punguan, 'tahun' => $tahun];
        if ($punguan === null) {
            return view('admin/keuangan_index', $data);
        }

        $pid       = (int) $punguan['id'];
        $kategori  = (new KeuanganKategoriModel())->milik($pid, false);
        $bulanan   = array_values(array_filter($kategori, static fn ($k) => $k['jenis'] === 'bulanan' && $k['is_active']));
        $katGrid   = (int) ($this->request->getGet('kategori') ?: ($bulanan[0]['id'] ?? 0));
        $statusCat = $this->request->getGet('status');
        $statusCat = array_key_exists((string) $statusCat, KeuanganCatatanModel::STATUS) ? $statusCat : null;

        $catatan = (new KeuanganCatatanModel())->lengkap()->where('keuangan_catatan.punguan_id', $pid);
        if ($statusCat !== null) {
            $catatan->where('keuangan_catatan.status', $statusCat);
        }

        return view('admin/keuangan_index', [
            ...$data,
            'kategori'   => $kategori,
            'bulanan'    => $bulanan,
            'katGrid'    => $katGrid,
            'grid'       => $katGrid ? $this->keuangan->rekapBulanan($pid, $katGrid, $tahun) : [],
            'ringkasan'  => $this->keuangan->ringkasan($pid, $tahun),
            'menunggu'   => (new KeuanganCatatanModel())->lengkap()->where('keuangan_catatan.punguan_id', $pid)->where('keuangan_catatan.status', 'menunggu')
                ->orderBy('keuangan_catatan.id', 'ASC')->findAll(200),
            'catatan'    => $catatan->orderBy('keuangan_catatan.id', 'DESC')->findAll(25),
            'statusCat'  => $statusCat,
            'validator'  => $this->user()->can('keuangan.validasi'),
            'anggotaAktif' => $this->punguan->rekap($pid)['aktif'],
        ]);
    }

    public function catat(): RedirectResponse|string
    {
        $daftar  = $this->punguan->daftarPunguan($this->user(), (int) $this->margaAktif()['id']);
        $punguan = $this->pilihPunguan($daftar);
        if ($punguan === null) {
            return redirect()->to('admin/keuangan');
        }

        if ($this->request->is('post')) {
            try {
                $ids = $this->keuangan->catat($this->user(), $this->request->getPost());
                $sah = $this->user()->can('keuangan.validasi');

                return redirect()->to('admin/keuangan?punguan=' . $punguan['id'])->with('sukses', sprintf(
                    '%d catatan disimpan%s.',
                    count($ids),
                    $sah ? ' dan langsung sah' : ', menunggu validasi Penatua',
                ));
            } catch (ValidasiDataException $e) {
                return redirect()->back()->withInput()->with('kesalahan', $e->errors());
            } catch (SilsilahException $e) {
                return redirect()->back()->withInput()->with('galat', $e->getMessage());
            }
        }

        return view('admin/keuangan_catat', [
            'daftar'   => $daftar,
            'punguan'  => $punguan,
            'anggota'  => $this->punguan->daftar((int) $punguan['id'], 'aktif'),
            'kategori' => (new KeuanganKategoriModel())->milik((int) $punguan['id']),
            'pilih'    => (int) $this->request->getGet('anggota'),
        ]);
    }

    public function validasi(int $id): RedirectResponse
    {
        try {
            $sah = $this->request->getPost('aksi') === 'sah';
            $this->keuangan->validasi($id, $this->user(), $sah, (string) $this->request->getPost('alasan'));

            return redirect()->back()->with('sukses', $sah ? 'Catatan disahkan.' : 'Catatan ditolak dan dikembalikan ke Humas.');
        } catch (SilsilahException $e) {
            return redirect()->back()->with('galat', $e->getMessage());
        }
    }

    public function validasiBanyak(): RedirectResponse
    {
        try {
            $ids = array_map('intval', (array) $this->request->getPost('ids'));
            $n   = $this->keuangan->validasiBanyak($ids, $this->user());

            return redirect()->back()->with('sukses', $n . ' catatan disahkan.');
        } catch (SilsilahException $e) {
            return redirect()->back()->with('galat', $e->getMessage());
        }
    }

    public function hapus(int $id): RedirectResponse
    {
        try {
            $this->keuangan->hapus($id, $this->user());

            return redirect()->back()->with('sukses', 'Catatan dihapus.');
        } catch (SilsilahException $e) {
            return redirect()->back()->with('galat', $e->getMessage());
        }
    }

    public function kategori(): RedirectResponse|string
    {
        $daftar  = $this->punguan->daftarPunguan($this->user(), (int) $this->margaAktif()['id']);
        $punguan = $this->pilihPunguan($daftar);
        if ($punguan === null) {
            return redirect()->to('admin/keuangan');
        }
        $model = new KeuanganKategoriModel();

        if ($this->request->is('post')) {
            $id = $this->request->getPost('id') ? (int) $this->request->getPost('id') : null;
            try {
                $this->keuangan->simpanKategori($this->user(), (int) $punguan['id'], $this->request->getPost(), $id);

                return redirect()->to('admin/keuangan/kategori?punguan=' . $punguan['id'])->with('sukses', 'Kategori disimpan.');
            } catch (ValidasiDataException $e) {
                return redirect()->back()->withInput()->with('kesalahan', $e->errors());
            } catch (SilsilahException $e) {
                return redirect()->back()->with('galat', $e->getMessage());
            }
        }

        $ubah = (int) $this->request->getGet('ubah');
        $k    = $ubah ? $model->find($ubah) : null;
        if ($k !== null && (int) $k['punguan_id'] !== (int) $punguan['id']) {
            $k = null;
        }

        return view('admin/keuangan_kategori', [
            'daftar'   => $daftar,
            'punguan'  => $punguan,
            'kategori' => $model->milik((int) $punguan['id'], false),
            'k'        => $k,
        ]);
    }

    /**
     * Riwayat keuangan satu member punguan.
     */
    public function anggota(int $keanggotaanId): string
    {
        try {
            $k = $this->punguan->ambil($keanggotaanId);
        } catch (SilsilahException) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (! $this->punguan->bolehKelola($this->user(), (int) $k['punguan_id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('admin/keuangan_anggota', [
            'k'         => $k,
            'riwayat'   => $this->keuangan->riwayat((int) $k['person_id'], 300, (int) $k['punguan_id']),
            'validator' => $this->user()->can('keuangan.validasi'),
        ]);
    }
}
