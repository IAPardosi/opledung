<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MargaModel;
use App\Models\PersonModel;
use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\LingkupAdmin;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Super Admin mengatur role, marga, dan tautan akun ke data silsilah.
 */
class Pengguna extends BaseController
{
    public function index(): string
    {
        $cari  = trim((string) $this->request->getGet('q'));
        $model = new UserModel();
        $model->select('users.*, marga.nama AS nama_marga, persons.nama_lengkap AS nama_person, persons.kode_anggota,
                (SELECT GROUP_CONCAT(g.`group`) FROM auth_groups_users g WHERE g.user_id = users.id) AS grup,
                (SELECT i.secret FROM auth_identities i WHERE i.user_id = users.id AND i.type = \'email_password\' LIMIT 1) AS email', false)
            ->join('marga', 'marga.id = users.marga_id', 'left')
            ->join('persons', 'persons.id = users.person_id', 'left')
            ->orderBy('users.id', 'DESC');

        if ($cari !== '') {
            $model->groupStart()->like('users.username', $cari)->orLike('persons.nama_lengkap', $cari)->groupEnd();
        }

        return view('admin/pengguna_index', [
            'rows'  => $model->asArray()->paginate(30),
            'pager' => $model->pager,
            'cari'  => $cari,
        ]);
    }

    public function ubah(int $id): RedirectResponse|string
    {
        $users = new UserModel();
        $user  = $users->findById($id);
        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $grupTersedia = array_keys(config('AuthGroups')->groups);

        if ($this->request->is('post')) {
            $grup    = (string) $this->request->getPost('grup');
            $margaId = $this->request->getPost('marga_id') ?: null;
            $kode    = strtoupper(trim((string) $this->request->getPost('kode_anggota')));

            if (! in_array($grup, $grupTersedia, true)) {
                return redirect()->back()->with('galat', 'Role tidak dikenal.');
            }
            if ($user->id === $this->user()->id && $grup !== 'superadmin') {
                return redirect()->back()->with('galat', 'Anda tidak dapat mencabut role Super Admin dari akun sendiri.');
            }

            $personId = null;
            if ($kode !== '') {
                $person = (new PersonModel())->where('kode_anggota', $kode)->first();
                if ($person === null) {
                    return redirect()->back()->withInput()->with('galat', "Kode anggota {$kode} tidak ditemukan.");
                }
                $dipakai = $users->where('person_id', $person->id)->where('id !=', $user->id)->first();
                if ($dipakai !== null) {
                    return redirect()->back()->withInput()->with('galat', "Kode anggota {$kode} sudah tertaut ke akun {$dipakai->username}.");
                }
                $personId = $person->id;
                $margaId ??= $person->marga_id;
            }

            // Lingkup Admin Wilayah: kab/kota dan cabang (kode anggota leluhur).
            $lingkup = [];
            foreach ((array) $this->request->getPost('lingkup_wilayah') as $kode) {
                if (is_string($kode) && preg_match('/^\d{2}(\.\d{2})?$/', $kode)) {
                    $lingkup[] = ['jenis' => 'wilayah', 'nilai' => $kode];
                }
            }
            foreach (preg_split('/[\s,;]+/', strtoupper((string) $this->request->getPost('lingkup_cabang')), -1, PREG_SPLIT_NO_EMPTY) as $kodeCabang) {
                $cabang = (new PersonModel())->where('kode_anggota', $kodeCabang)->first();
                if ($cabang === null) {
                    return redirect()->back()->withInput()->with('galat', "Kode cabang {$kodeCabang} tidak ditemukan.");
                }
                $lingkup[] = ['jenis' => 'cabang', 'nilai' => (string) $cabang->id];
            }
            foreach ((array) $this->request->getPost('lingkup_punguan') as $pid) {
                if (is_numeric($pid)) {
                    $lingkup[] = ['jenis' => 'punguan', 'nilai' => (string) (int) $pid];
                }
            }
            if (in_array($grup, ['admin_wilayah', 'penatua'], true) && $lingkup === []) {
                return redirect()->back()->withInput()->with('galat', 'Penatua Punguan dan Admin Wilayah wajib diberi minimal satu punguan, wilayah, atau cabang.');
            }
            $punguanId = $this->request->getPost('punguan_id') ?: null;

            $lama = ['grup' => $user->getGroups(), 'marga_id' => $user->marga_id, 'person_id' => $user->person_id, 'active' => $user->active];
            $baru = ['grup' => [$grup], 'marga_id' => $margaId, 'person_id' => $personId, 'active' => (bool) $this->request->getPost('active')];

            $users->update($user->id, ['marga_id' => $margaId, 'person_id' => $personId, 'punguan_id' => $punguanId]);
            (new LingkupAdmin())->simpan($user->id, in_array($grup, ['admin_wilayah', 'penatua'], true) ? $lingkup : []);
            $baru['lingkup'] = $lingkup;
            $user->syncGroups($grup);
            $baru['active'] ? $user->activate() : $user->deactivate();

            (new AuditLogger())->catat('ubah', 'users', $user->id, $lama, $baru, $this->user()->id);

            return redirect()->to('admin/pengguna')->with('sukses', 'Akun ' . $user->username . ' diperbarui.');
        }

        $lingkup = (new LingkupAdmin())->daftar($user->id);
        $cabangIds = array_map('intval', array_column(array_filter($lingkup, static fn ($l) => $l['jenis'] === 'cabang'), 'nilai'));

        return view('admin/pengguna_form', [
            'punguan'         => (new \App\Models\PunguanModel())->findAll(),
            'punguanLingkup'  => array_column(array_filter($lingkup, static fn ($l) => $l['jenis'] === 'punguan'), 'nilai'),
            'wilayahTerpilih' => array_column(array_filter($lingkup, static fn ($l) => $l['jenis'] === 'wilayah'), 'nilai'),
            'cabang'          => $cabangIds === [] ? [] : (new PersonModel())->whereIn('id', $cabangIds)->findAll(),
            'akun'   => $user,
            'grup'   => $user->getGroups()[0] ?? 'member',
            'label'  => config('AuthGroups')->groups,
            'margas' => (new MargaModel())->findAll(),
            'person' => $user->person_id ? (new PersonModel())->find($user->person_id) : null,
        ]);
    }
}
