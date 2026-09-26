<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\Person;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\ChangeRequestModel;
use App\Models\PersonModel;
use App\Models\UserModel;
use App\Models\WilayahModel;
use App\Services\DataPribadiCipher;
use App\Services\PartuturanService;
use App\Services\PersonService;
use App\Services\SilsilahPolicy;
use App\Services\SilsilahQuery;
use App\Services\UsulanService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman per anggota (wajib login): profil, tambah anak/pasangan, ubah data.
 *
 * Bila pengguna berhak mengelola generasi tersebut, perubahan langsung disimpan;
 * bila tidak, perubahan diajukan sebagai usulan untuk diverifikasi.
 */
class Anggota extends BaseController
{
    private PersonService $service;
    private SilsilahPolicy $policy;

    public function __construct()
    {
        $this->service = new PersonService();
        $this->policy  = new SilsilahPolicy();
    }

    public function profil(int $id): string
    {
        $query  = new SilsilahQuery();
        $profil = $query->profil($id);
        if ($profil === null) {
            throw PageNotFoundException::forPageNotFound('Anggota tidak ditemukan.');
        }

        /** @var Person $p */
        $p    = $profil['person'];
        $user = $this->user();
        $diri = $user->person_id !== null && (int) $user->person_id === $p->id;

        $sensitif = $this->policy->bolehLihatDataSensitif($user, $p);
        $cipher   = $sensitif ? new DataPribadiCipher() : null;

        $bisaKlaim = false;
        if ($user->person_id === null && (int) $user->marga_id === $p->marga_id) {
            try {
                (new UsulanService())->pastikanBisaDiklaim($user, $p);
                $bisaKlaim = true;
            } catch (SilsilahException) {
            }
        }

        $tutur = $user->person_id !== null && ! $diri
            ? (new PartuturanService())->hubungan((int) $user->person_id, $p->id)
            : null;

        return view('anggota/profil', [
            ...$profil,
            'tutur'         => $tutur,
            'saya'          => $tutur !== null ? (new PersonModel())->find((int) $user->person_id) : null,
            'diri'          => $diri,
            'lihatKontak'   => ! $p->isHidup() || ! $p->sembunyikan_kontak || $diri || $sensitif,
            'nik'           => $cipher ? DataPribadiCipher::samarkan($cipher->dekripsi($p->nik_enc)) : null,
            'noKk'          => $cipher ? DataPribadiCipher::samarkan($cipher->dekripsi($p->no_kk_enc)) : null,
            'lihatSensitif' => $sensitif,
            'wilayah'       => $this->namaWilayah($p),
            'kelola'        => $this->policy->bolehKelolaGenerasi($user, $p->marga_id, $p->generasi_ke, $p),
            'kelolaAnak'    => $this->policy->bolehKelolaGenerasi($user, $p->marga_id, $p->generasi_ke + 1, $p),
            'ubahLangsung'  => $this->policy->bolehUbahProfil($user, $p),
            'bolehValidasi' => $this->policy->bolehValidasi($user, $p) && ! $p->isTerkunci() && $user->can('silsilah.verify', 'silsilah.pokok'),
            'bolehUsul'     => $user->can('silsilah.propose') && (int) $user->marga_id === $p->marga_id,
            'bisaKlaim'     => $bisaKlaim,
            'klaimPending'  => (new ChangeRequestModel())->where(['user_id' => $user->id, 'jenis' => 'klaim_profil', 'status' => 'pending'])->countAllResults() > 0,
            'akunTertaut'   => (new UserModel())->where('person_id', $p->id)->first(),
            'pokok'         => $this->policy->isSilsilahPokok($p->marga_id, $p->generasi_ke),
        ]);
    }

    public function profilSaya(): RedirectResponse|string
    {
        $user = $this->user();
        if ($user->person_id !== null) {
            return redirect()->to('anggota/' . $user->person_id);
        }

        return view('anggota/profil_saya_kosong');
    }

    public function usulanSaya(): string
    {
        $rows = (new ChangeRequestModel())
            ->select('change_requests.*, persons.nama_lengkap, persons.kode_anggota')
            ->join('persons', 'persons.id = change_requests.person_id', 'left')
            ->where('change_requests.user_id', $this->user()->id)
            ->orderBy('change_requests.id', 'DESC')
            ->findAll(100);

        return view('anggota/usulan_saya', ['rows' => $rows]);
    }

    public function tambahAnak(int $id): RedirectResponse|string
    {
        $induk = $this->ambil($id);
        if (! $induk->bisaPunyaAnak()) {
            return redirect()->to('anggota/' . $id)->with('galat', 'Keturunan dari anak boru dan pasangan tidak diteruskan dalam silsilah marga.');
        }

        $langsung = $this->policy->bolehKelolaGenerasi($this->user(), $induk->marga_id, $induk->generasi_ke + 1, $induk);

        if ($this->request->is('post')) {
            $data = $this->dataForm();
            $data['pasangan_id'] = $this->request->getPost('pasangan_id');

            return $this->simpanAtauUsul(
                $langsung,
                fn () => $this->service->tambahAnak($induk->id, $data, $this->user()),
                'tambah_anak',
                $induk,
                ['data' => $data],
                'Anak berhasil ditambahkan.',
            );
        }

        return view('anggota/form', [
            'judul'    => ($langsung ? 'Tambah Anak' : 'Usulkan Anak') . ' dari ' . $induk->nama_lengkap,
            'mode'     => 'anak',
            'induk'    => $induk,
            'person'   => null,
            'langsung' => $langsung,
            'pasangan' => (new SilsilahQuery())->pasangan($induk->id),
            'kandidat' => $langsung ? [] : (new UsulanService())->kandidatValidator('tambah_anak', $induk),
            'aksi'     => site_url("anggota/{$id}/tambah-anak"),
        ]);
    }

    public function tambahPasangan(int $id): RedirectResponse|string
    {
        $person = $this->ambil($id);
        if (! $person->isAnggotaGarisMarga()) {
            return redirect()->to('anggota/' . $id)->with('galat', 'Pasangan hanya dapat ditambahkan untuk anggota garis utama atau boru.');
        }

        $langsung = $this->policy->bolehKelolaGenerasi($this->user(), $person->marga_id, $person->generasi_ke, $person);

        if ($this->request->is('post')) {
            $data       = $this->dataForm();
            $pernikahan = [
                'tanggal_nikah' => $this->request->getPost('tanggal_nikah'),
                'tempat_nikah'  => $this->request->getPost('tempat_nikah'),
                'status'        => in_array($this->request->getPost('status_nikah'), ['menikah', 'cerai_hidup', 'cerai_mati'], true) ? $this->request->getPost('status_nikah') : 'menikah',
            ];

            return $this->simpanAtauUsul(
                $langsung,
                fn () => $this->service->tambahPasangan($person->id, $data, $pernikahan, $this->user()),
                'tambah_pasangan',
                $person,
                ['data' => $data, 'pernikahan' => $pernikahan],
                'Pasangan berhasil ditambahkan.',
                $person->id,
            );
        }

        return view('anggota/form', [
            'judul'    => ($langsung ? 'Tambah ' : 'Usulkan ') . ($person->jenis_kelamin === 'L' ? 'Istri' : 'Suami') . ' untuk ' . $person->nama_lengkap,
            'mode'     => 'pasangan',
            'induk'    => $person,
            'person'   => null,
            'langsung' => $langsung,
            'pasangan' => [],
            'kandidat' => $langsung ? [] : (new UsulanService())->kandidatValidator('tambah_pasangan', $person),
            'aksi'     => site_url("anggota/{$id}/tambah-pasangan"),
        ]);
    }

    public function ubah(int $id): RedirectResponse|string
    {
        $person   = $this->ambil($id);
        $langsung = $this->policy->bolehUbahProfil($this->user(), $person);

        if ($person->isTerkunci() && ! $langsung) {
            return redirect()->to('anggota/' . $id)->with('galat', 'Data ini sudah dikunci Ketua Adat. Perubahan hanya dapat dilakukan oleh Ketua Adat.');
        }

        if ($this->request->is('post')) {
            $data = $this->dataForm(true);

            if ($langsung) {
                $foto = $this->request->getFile('foto');
                if ($foto !== null && $foto->isValid()) {
                    $hasil = $this->simpanFoto($person);
                    if ($hasil instanceof RedirectResponse) {
                        return $hasil;
                    }
                    $data['foto'] = $hasil;
                }
            }

            return $this->simpanAtauUsul(
                $langsung,
                fn () => $this->service->ubahProfil($person->id, $data, $this->user()),
                'ubah_data',
                $person,
                ['data' => $this->hanyaYangBerubah($person, $data)],
                'Data berhasil diperbarui.',
                $person->id,
            );
        }

        return view('anggota/form', [
            'judul'    => ($langsung ? 'Ubah Data ' : 'Usulkan Perubahan Data ') . $person->nama_lengkap,
            'mode'     => 'ubah',
            'induk'    => null,
            'person'   => $person,
            'langsung' => $langsung,
            'pasangan' => [],
            'kandidat' => $langsung ? [] : (new UsulanService())->kandidatValidator('ubah_data', $person),
            'aksi'     => site_url("anggota/{$id}/ubah"),
        ]);
    }

    public function validasi(int $id): RedirectResponse
    {
        try {
            $p = $this->service->validasi($id, $this->user());

            return redirect()->to('anggota/' . $id)->with('sukses', $p->isTerkunci() ? 'Data divalidasi dan dikunci sebagai Silsilah Pokok.' : 'Data ditandai terverifikasi.');
        } catch (SilsilahException $e) {
            return redirect()->to('anggota/' . $id)->with('galat', $e->getMessage());
        }
    }

    public function hapus(int $id): RedirectResponse
    {
        $person = $this->ambil($id);

        try {
            $this->service->hapus($id, $this->user());
        } catch (SilsilahException $e) {
            return redirect()->to('anggota/' . $id)->with('galat', $e->getMessage());
        }

        return redirect()->to($person->induk_id ? 'anggota/' . $person->induk_id : 'generasi')->with('sukses', 'Data ' . $person->nama_lengkap . ' telah dihapus.');
    }

    public function klaim(int $id): RedirectResponse
    {
        $person = $this->ambil($id);

        try {
            (new UsulanService())->ajukan($this->user(), 'klaim_profil', $person, [], $this->request->getPost('catatan'), $this->validatorDipilih());
        } catch (SilsilahException $e) {
            return redirect()->to('anggota/' . $id)->with('galat', $e->getMessage());
        }

        return redirect()->to('anggota/' . $id)->with('sukses', 'Permintaan "Ini saya" telah dikirim dan menunggu verifikasi.');
    }

    public function foto(int $id): ResponseInterface
    {
        $person = $this->ambil($id);
        $path   = WRITEPATH . 'uploads/foto/' . basename((string) $person->foto);

        if (! $person->foto || ! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path) ?: 'image/jpeg')
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody((string) file_get_contents($path));
    }

    private function validatorDipilih(): ?int
    {
        $v = $this->request->getPost('validator_user_id');

        return is_numeric($v) ? (int) $v : null;
    }

    private function ambil(int $id): Person
    {
        $person = (new PersonModel())->find($id);
        if ($person === null) {
            throw PageNotFoundException::forPageNotFound('Anggota tidak ditemukan.');
        }

        return $person;
    }

    /**
     * Menyimpan langsung, atau mengajukan usulan bila pengguna tidak berhak menyimpan langsung.
     *
     * @param array<string, mixed> $payload
     */
    private function simpanAtauUsul(
        bool $langsung,
        callable $simpan,
        string $jenis,
        Person $target,
        array $payload,
        string $pesanSukses,
        ?int $kembaliKe = null,
    ): RedirectResponse {
        try {
            if ($langsung) {
                $hasil = $simpan();

                return redirect()->to('anggota/' . ($kembaliKe ?? $hasil->id))->with('sukses', $pesanSukses);
            }

            if ($jenis === 'ubah_data' && $payload['data'] === []) {
                return redirect()->to('anggota/' . $target->id)->with('info', 'Tidak ada perubahan data.');
            }

            (new UsulanService())->ajukan($this->user(), $jenis, $target, $payload, $this->request->getPost('catatan_pengusul'), $this->validatorDipilih());

            return redirect()->to('anggota/' . $target->id)->with('sukses', 'Usulan telah dikirim dan menunggu verifikasi.');
        } catch (ValidasiDataException $e) {
            return redirect()->back()->withInput()->with('kesalahan', $e->errors());
        } catch (SilsilahException $e) {
            return redirect()->back()->withInput()->with('galat', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(bool $ubah = false): array
    {
        $kolom = [...PersonModel::KOLOM_PROFIL, 'nik', 'no_kk', 'jenis_kelamin', 'urutan_anak'];
        $kolom = array_diff($kolom, ['foto', 'sembunyikan_kontak']);
        if ($ubah) {
            $kolom = array_diff($kolom, ['jenis_kelamin', 'urutan_anak']);
        }

        $data = [];
        foreach ($kolom as $k) {
            $v = $this->request->getPost($k);
            if ($v !== null) {
                $data[$k] = is_string($v) ? trim($v) : $v;
            }
        }
        $data['sembunyikan_kontak'] = $this->request->getPost('sembunyikan_kontak') ? 1 : 0;

        // NIK/No. KK kosong pada form ubah berarti "tidak diubah".
        foreach (['nik', 'no_kk'] as $k) {
            if ($ubah && ($data[$k] ?? '') === '') {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function hanyaYangBerubah(Person $person, array $data): array
    {
        $lama = $person->toRawArray();

        return array_filter(
            $data,
            static fn ($v, string $k): bool => in_array($k, ['nik', 'no_kk'], true) || (string) ($lama[$k] ?? '') !== (string) ($v ?? ''),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function simpanFoto(Person $person): RedirectResponse|string
    {
        $aturan = ['foto' => [
            'label' => 'Foto',
            'rules' => 'is_image[foto]|max_size[foto,2048]|mime_in[foto,image/jpeg,image/png,image/webp]',
        ]];
        if (! $this->validate($aturan)) {
            return redirect()->back()->withInput()->with('kesalahan', $this->validator->getErrors());
        }

        $file = $this->request->getFile('foto');
        $dir  = WRITEPATH . 'uploads/foto/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nama = $person->id . '-' . bin2hex(random_bytes(8)) . '.jpg';
        service('image')->withFile($file->getTempName())
            ->resize(800, 800, true)
            ->convert(IMAGETYPE_JPEG)
            ->save($dir . $nama, 85);

        if ($person->foto && is_file($dir . basename($person->foto))) {
            unlink($dir . basename($person->foto));
        }

        return $nama;
    }

    /**
     * @return array<string, string>
     */
    private function namaWilayah(Person $p): array
    {
        $kode = array_filter([$p->provinsi_kode, $p->kabupaten_kode, $p->kecamatan_kode, $p->desa_kode]);
        if ($kode === []) {
            return [];
        }

        return array_column((new WilayahModel())->whereIn('kode', $kode)->findAll(), 'nama', 'kode');
    }
}
