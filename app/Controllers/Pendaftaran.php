<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\ChangeRequestModel;
use App\Models\PersonModel;
use App\Models\PunguanModel;
use App\Models\UserModel;
use App\Services\UsulanService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pendaftaran kepala keluarga beserta silsilah dan keluarganya, klaim "Ini saya",
 * serta validasi keluarga oleh kerabat garis langsung.
 *
 * Alur: daftar akun → isi silsilah + istri + anak, tunjuk validator keluarga
 *       → validator keluarga (ayah/ompung atau anak/pahompu) menyatakan benar
 *       → penatua punguan mengesahkan → akun menjadi member.
 */
class Pendaftaran extends BaseController
{
    public function index(): RedirectResponse|string
    {
        $user = $this->user();
        if ($user->person_id !== null) {
            return redirect()->to('profil-saya');
        }

        if ($this->request->is('post')) {
            return $this->simpan();
        }

        $aktif = (new ChangeRequestModel())->where('user_id', $user->id)->whereIn('jenis', ['daftar_anggota', 'klaim_profil'])
            ->orderBy('id', 'DESC')->first();

        if ($aktif !== null && $aktif['status'] === 'pending') {
            $service = new UsulanService();

            return view('pendaftaran/status', [
                'usulan'    => $aktif,
                'acuan'     => (new PersonModel())->find((int) $aktif['person_id']),
                'data'      => $service->dataTampil($aktif),
                'validator' => $aktif['validator_user_id'] ? (new UserModel())->findById((int) $aktif['validator_user_id']) : null,
                'kesaksian' => $service->kesaksian((int) $aktif['id']),
            ]);
        }

        $marga = $this->margaAktif();

        return view('pendaftaran/form', [
            'ditolak' => $aktif !== null && $aktif['status'] === 'ditolak' ? $aktif : null,
            'batas'   => (int) ($marga['batas_silsilah_pokok'] ?? 10),
            'maks'    => UsulanService::MAKS_ANTARA,
            'punguan' => (new PunguanModel())->aktif((int) $marga['id']),
        ]);
    }

    /**
     * Calon validator keluarga untuk leluhur dan jumlah generasi antara yang dipilih (JSON).
     */
    public function validator(): ResponseInterface
    {
        $leluhur = (new PersonModel())->find((int) $this->request->getGet('leluhur'));
        if ($leluhur === null) {
            return $this->response->setJSON([]);
        }

        return $this->response->setJSON(
            (new UsulanService())->kandidatValidator('daftar_anggota', $leluhur, max(0, (int) $this->request->getGet('antara'))),
        );
    }

    /**
     * "Ini saya": konfirmasi klaim beserta pilihan validator keluarga.
     */
    public function klaim(int $id): RedirectResponse|string
    {
        $user   = $this->user();
        $person = (new PersonModel())->find($id);
        if ($person === null) {
            return redirect()->to('pendaftaran')->with('galat', 'Data tidak ditemukan.');
        }

        $service = new UsulanService();

        if ($this->request->is('get')) {
            try {
                $service->pastikanBisaDiklaim($user, $person);
            } catch (SilsilahException $e) {
                return redirect()->back()->with('galat', $e->getMessage());
            }

            return view('pendaftaran/klaim', [
                'person'   => $person,
                'kandidat' => $service->kandidatValidator('klaim_profil', $person),
                'punguan'  => (new PunguanModel())->aktif($person->marga_id),
            ]);
        }

        // Calon member mengikuti marga dan punguan pilihannya.
        $ubah = [];
        if ((int) $user->marga_id !== $person->marga_id) {
            $ubah['marga_id'] = $person->marga_id;
        }
        if ($this->request->getPost('punguan_id')) {
            $ubah['punguan_id'] = (int) $this->request->getPost('punguan_id');
        }
        if ($ubah !== []) {
            (new UserModel())->update($user->id, $ubah);
            foreach ($ubah as $k => $v) {
                $user->{$k} = $v;
            }
        }

        try {
            $service->ajukan($user, 'klaim_profil', $person, [], $this->request->getPost('catatan'), $this->angka('validator_user_id'));
        } catch (ValidasiDataException $e) {
            return redirect()->back()->withInput()->with('kesalahan', $e->errors());
        } catch (SilsilahException $e) {
            return redirect()->back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->to($user->inGroup('calon') ? 'pendaftaran' : 'usulan-saya')
            ->with('sukses', 'Permintaan "Ini saya" dikirim ke validator keluarga dan penatua punguan.');
    }

    public function konfirmasi(): string
    {
        $service = new UsulanService();
        $user    = $this->user();

        $siapkan = static function (array $rows) use ($service): array {
            foreach ($rows as &$r) {
                $r['data']      = $service->dataTampil($r);
                $r['generasi']  = (int) ($r['generasi_acuan'] ?? $r['generasi_leluhur'] ?? 0) + count($r['payload']['antara'] ?? []) + 1;
                $r['kesaksian'] = $service->kesaksian((int) $r['id']);
                $r['acuan']     = (new PersonModel())->find((int) $r['person_id']);
            }

            return $rows;
        };

        return view('pendaftaran/konfirmasi', [
            'tugas'     => $siapkan($service->tugasValidasi($user)),
            'kesaksian' => $siapkan(array_values(array_filter(
                $service->menungguKesaksian($user),
                static fn ($r) => (int) ($r['validator_user_id'] ?? 0) !== $user->id,
            ))),
        ]);
    }

    public function simpanValidasi(int $id): RedirectResponse
    {
        $pilihan = $this->request->getPost('benar');
        if (! in_array($pilihan, ['1', '0'], true)) {
            return redirect()->back()->with('galat', 'Pilih "Benar" atau "Tidak benar".');
        }

        try {
            (new UsulanService())->validasiKeluarga($id, $this->user(), $pilihan === '1', $this->request->getPost('catatan'));
        } catch (SilsilahException $e) {
            return redirect()->to('konfirmasi-keluarga')->with('galat', $e->getMessage());
        }

        return redirect()->to('konfirmasi-keluarga')->with('sukses', $pilihan === '1'
            ? 'Terima kasih. Data dinyatakan benar dan diteruskan ke penatua punguan.'
            : 'Tercatat tidak benar. Penatua punguan akan menolak dan pengusul dapat memperbaikinya.');
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

        $antara = $this->baris('antara', ['nama', 'tahun', 'hidup'], static fn (array $r): array => [
            'nama_lengkap' => mb_substr(trim((string) $r['nama']), 0, 150),
            'tahun_lahir'  => is_numeric($r['tahun']) ? (int) $r['tahun'] : null,
            'status_hidup' => in_array($r['hidup'], ['hidup', 'meninggal'], true) ? $r['hidup'] : 'tidak_diketahui',
        ]);
        $anak = $this->baris('anak', ['nama', 'jk', 'tahun'], static fn (array $r): array => [
            'nama_lengkap'  => $r['nama'],
            'jenis_kelamin' => $r['jk'],
            'tahun_lahir'   => $r['tahun'],
        ]);

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
        $data['status_hidup']      = 'hidup';
        $data['status_perkawinan'] = trim((string) $this->request->getPost('istri_nama')) !== '' ? 'Kawin' : null;
        $data = array_filter($data, static fn ($v) => $v !== null);

        $istri = [
            'nama_lengkap' => $this->request->getPost('istri_nama'),
            'marga_nama'   => $this->request->getPost('istri_marga'),
            'tahun_lahir'  => $this->request->getPost('istri_tahun'),
        ];

        $punguanId = $this->angka('punguan_id');
        if ($punguanId !== null && (new PunguanModel())->where(['id' => $punguanId, 'is_active' => 1])->countAllResults() === 0) {
            $punguanId = null;
        }

        try {
            (new UsulanService())->ajukanPendaftaran(
                $this->user(),
                $leluhur,
                $antara,
                $data,
                trim((string) $this->request->getPost('catatan')) ?: null,
                ['istri' => $istri, 'anak' => $anak],
                $punguanId,
                $this->angka('validator_user_id'),
            );
        } catch (ValidasiDataException $e) {
            return redirect()->back()->withInput()->with('kesalahan', $e->errors());
        } catch (SilsilahException $e) {
            return redirect()->back()->withInput()->with('galat', $e->getMessage());
        }

        return redirect()->to('pendaftaran')->with('sukses', 'Pendaftaran keluarga terkirim. Validator keluarga Anda akan diminta memastikan datanya.');
    }

    /**
     * Membaca kolom berulang dari form, mis. antara_nama[] + antara_tahun[].
     *
     * @param list<string> $kolom
     *
     * @return list<array<string, mixed>>
     */
    private function baris(string $awalan, array $kolom, callable $bentuk): array
    {
        $nilai = [];
        foreach ($kolom as $k) {
            $nilai[$k] = (array) $this->request->getPost("{$awalan}_{$k}");
        }

        $hasil = [];
        foreach (array_keys($nilai[$kolom[0]]) as $i) {
            $r = [];
            foreach ($kolom as $k) {
                $r[$k] = trim((string) ($nilai[$k][$i] ?? ''));
            }
            if ($r[$kolom[0]] !== '') {
                $hasil[] = $bentuk($r);
            }
        }

        return $hasil;
    }

    private function angka(string $kunci): ?int
    {
        $v = $this->request->getPost($kunci);

        return is_numeric($v) ? (int) $v : null;
    }
}
