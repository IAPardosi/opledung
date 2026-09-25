<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\ChangeRequestModel;
use App\Models\PersonModel;
use App\Models\UserModel;
use App\Services\UsulanService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Pendaftaran member beserta silsilahnya, dan kesaksian keluarga.
 *
 * Alur: daftar akun → isi silsilah (leluhur terdekat + generasi antara + data diri)
 *       → kesaksian kerabat → validasi Admin Wilayah/Admin Marga → menjadi member.
 */
class Pendaftaran extends BaseController
{
    public function index(): RedirectResponse|string
    {
        $user = $this->user();
        if ($user->person_id !== null) {
            return redirect()->to('profil-saya');
        }

        $usulan = new ChangeRequestModel();
        $aktif  = $usulan->where('user_id', $user->id)->whereIn('jenis', ['daftar_anggota', 'klaim_profil'])
            ->orderBy('id', 'DESC')->first();

        if ($this->request->is('post')) {
            return $this->simpan();
        }

        if ($aktif !== null && $aktif['status'] === 'pending') {
            $service = new UsulanService();

            return view('pendaftaran/status', [
                'usulan'    => $aktif,
                'leluhur'   => (new PersonModel())->find((int) $aktif['person_id']),
                'data'      => $service->dataTampil($aktif),
                'kesaksian' => $service->kesaksian((int) $aktif['id']),
            ]);
        }

        return view('pendaftaran/form', [
            'ditolak' => $aktif !== null && $aktif['status'] === 'ditolak' ? $aktif : null,
            'batas'   => (int) ($this->margaAktif()['batas_silsilah_pokok'] ?? 10),
            'maks'    => UsulanService::MAKS_ANTARA,
        ]);
    }

    public function klaim(int $id): RedirectResponse
    {
        $user   = $this->user();
        $person = (new PersonModel())->find($id);
        if ($person === null) {
            return redirect()->to('pendaftaran')->with('galat', 'Data tidak ditemukan.');
        }

        // Calon member mengikuti marga data yang diklaimnya.
        if ((int) $user->marga_id !== $person->marga_id) {
            (new UserModel())->update($user->id, ['marga_id' => $person->marga_id]);
            $user->marga_id = $person->marga_id;
        }

        try {
            (new UsulanService())->ajukan($user, 'klaim_profil', $person, [], $this->request->getPost('catatan'));
        } catch (SilsilahException $e) {
            return redirect()->to('pendaftaran')->with('galat', $e->getMessage());
        }

        return redirect()->to('pendaftaran')->with('sukses', 'Permintaan "Ini saya" dikirim. Admin akan memeriksa bahwa data ini benar Anda.');
    }

    public function konfirmasi(): string
    {
        $service = new UsulanService();
        $rows    = $service->menungguKesaksian($this->user());
        $persons = new PersonModel();

        foreach ($rows as &$r) {
            $r['data']      = $service->dataTampil($r);
            $r['generasi']  = (int) $r['generasi_leluhur'] + count($r['payload']['antara'] ?? []) + 1;
            $r['kesaksian'] = $service->kesaksian((int) $r['id']);
            if ($r['jenis'] === 'klaim_profil') {
                $r['diklaim'] = $persons->find((int) $r['person_id']);
            }
        }
        unset($r);

        return view('pendaftaran/konfirmasi', ['rows' => $rows]);
    }

    public function simpanKonfirmasi(int $id): RedirectResponse
    {
        $pilihan = $this->request->getPost('benar');
        if (! in_array($pilihan, ['1', '0'], true)) {
            return redirect()->back()->with('galat', 'Pilih "Benar" atau "Tidak benar".');
        }

        try {
            (new UsulanService())->konfirmasi($id, $this->user(), $pilihan === '1', $this->request->getPost('catatan'));
        } catch (SilsilahException $e) {
            return redirect()->to('konfirmasi-keluarga')->with('galat', $e->getMessage());
        }

        return redirect()->to('konfirmasi-keluarga')->with('sukses', 'Terima kasih, kesaksian Anda tercatat.');
    }

    private function simpan(): RedirectResponse
    {
        $leluhur = (new PersonModel())->find((int) $this->request->getPost('leluhur_id'));
        if ($leluhur === null) {
            return redirect()->back()->withInput()->with('galat', 'Pilih leluhur terdekat Anda yang sudah tercatat di silsilah.');
        }

        $nama   = (array) $this->request->getPost('antara_nama');
        $tahun  = (array) $this->request->getPost('antara_tahun');
        $hidup  = (array) $this->request->getPost('antara_hidup');
        $antara = [];
        foreach ($nama as $i => $n) {
            if (trim((string) $n) === '') {
                continue;
            }
            $antara[] = [
                'nama_lengkap' => mb_substr(trim((string) $n), 0, 150),
                'tahun_lahir'  => is_numeric($tahun[$i] ?? null) ? (int) $tahun[$i] : null,
                'status_hidup' => in_array($hidup[$i] ?? null, ['hidup', 'meninggal'], true) ? $hidup[$i] : 'tidak_diketahui',
            ];
        }

        $data = [];
        foreach ([
            'nama_lengkap', 'nama_panggilan', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'nama_ibu',
            'no_hp', 'alamat_jalan', 'provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'desa_kode', 'pekerjaan',
        ] as $k) {
            $v = trim((string) $this->request->getPost($k));
            if ($v !== '') {
                $data[$k] = $v;
            }
        }
        $data['status_hidup'] = 'hidup';

        $catatan = trim(implode("\n", array_filter([
            $this->request->getPost('kerabat') ? 'Kerabat yang mengenal saya: ' . trim((string) $this->request->getPost('kerabat')) : null,
            trim((string) $this->request->getPost('catatan')),
        ])));

        try {
            (new UsulanService())->ajukanPendaftaran($this->user(), $leluhur, $antara, $data, $catatan ?: null);
        } catch (ValidasiDataException $e) {
            return redirect()->back()->withInput()->with('kesalahan', $e->errors());
        } catch (SilsilahException $e) {
            return redirect()->back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->to('pendaftaran')->with('sukses', 'Pendaftaran silsilah terkirim. Mintalah kerabat Anda memberi kesaksian agar validasi lebih cepat.');
    }
}
