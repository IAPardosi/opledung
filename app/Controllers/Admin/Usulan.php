<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Models\ChangeRequestModel;
use App\Models\PersonModel;
use App\Models\WilayahModel;
use App\Services\UsulanService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Usulan extends BaseController
{
    public function index(): string
    {
        $user   = $this->user();
        $status = $this->request->getGet('status') ?: 'pending';
        $model  = new ChangeRequestModel();

        $model->select('change_requests.*, persons.nama_lengkap, persons.kode_anggota, persons.generasi_ke, users.username')
            ->join('persons', 'persons.id = change_requests.person_id', 'left')
            ->join('users', 'users.id = change_requests.user_id', 'left')
            ->orderBy('change_requests.id', $status === 'pending' ? 'ASC' : 'DESC');

        if (in_array($status, ['pending', 'disetujui', 'ditolak'], true)) {
            $model->where('change_requests.status', $status);
        }
        if (! $user->inGroup('superadmin')) {
            $model->where('change_requests.marga_id', $user->marga_id);
        }

        return view('admin/usulan_index', [
            'rows'   => $model->paginate(30),
            'pager'  => $model->pager,
            'status' => $status,
        ]);
    }

    public function detail(int $id): string
    {
        $service = new UsulanService();
        $usulan  = (new ChangeRequestModel())
            ->select('change_requests.*, users.username')
            ->join('users', 'users.id = change_requests.user_id', 'left')
            ->find($id);

        if ($usulan === null || ! $service->bolehVerifikasi($this->user(), (int) $usulan['marga_id'])) {
            throw PageNotFoundException::forPageNotFound('Usulan tidak ditemukan.');
        }

        $persons = new PersonModel();

        return view('admin/usulan_detail', [
            'usulan' => $usulan,
            'data'   => $this->labelNilai($service->dataTampil($usulan)),
            'target' => $usulan['person_id'] ? $persons->withDeleted()->find($usulan['person_id']) : null,
            'hasil'  => $usulan['hasil_person_id'] ? $persons->find($usulan['hasil_person_id']) : null,
        ]);
    }

    public function setujui(int $id): RedirectResponse
    {
        try {
            (new UsulanService())->setujui($id, $this->user(), $this->request->getPost('catatan'));
        } catch (SilsilahException $e) {
            return redirect()->to('admin/usulan/' . $id)->with('galat', $e->getMessage());
        }

        return redirect()->to('admin/usulan')->with('sukses', 'Usulan disetujui dan sudah masuk ke silsilah.');
    }

    public function tolak(int $id): RedirectResponse
    {
        try {
            (new UsulanService())->tolak($id, $this->user(), (string) $this->request->getPost('catatan'));
        } catch (SilsilahException $e) {
            return redirect()->to('admin/usulan/' . $id)->with('galat', $e->getMessage());
        }

        return redirect()->to('admin/usulan')->with('sukses', 'Usulan ditolak.');
    }

    /**
     * Mengganti kode (L/P, hidup, kode wilayah, ID pasangan) dengan teks yang mudah dibaca.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function labelNilai(array $data): array
    {
        $cfg = config('Silsilah');

        if (isset($data['jenis_kelamin'])) {
            $data['jenis_kelamin'] = $cfg->jenisKelamin[$data['jenis_kelamin']] ?? $data['jenis_kelamin'];
        }
        if (isset($data['status_hidup'])) {
            $data['status_hidup'] = $cfg->statusHidup[$data['status_hidup']] ?? $data['status_hidup'];
        }
        if (array_key_exists('sembunyikan_kontak', $data)) {
            $data['sembunyikan_kontak'] = $data['sembunyikan_kontak'] ? 'Ya' : null;
        }
        if (! empty($data['pasangan_id'])) {
            $data['pasangan_id'] = (new PersonModel())->find((int) $data['pasangan_id'])?->nama_lengkap ?? $data['pasangan_id'];
        }

        $kode = array_filter(array_intersect_key($data, array_flip(['provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'desa_kode'])));
        if ($kode !== []) {
            $nama = array_column((new WilayahModel())->whereIn('kode', array_values($kode))->findAll(), 'nama', 'kode');
            foreach ($kode as $k => $v) {
                $data[$k] = $nama[$v] ?? $v;
            }
        }

        return $data;
    }
}
