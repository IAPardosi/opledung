<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\PersonModel;
use App\Services\PersonService;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Ketua Adat menetapkan leluhur awal (Generasi 1) marganya.
 */
class LeluhurAwal extends BaseController
{
    public function index(): RedirectResponse|string
    {
        $marga   = $this->margaAktif();
        $leluhur = (new PersonModel())->leluhurAwal((int) $marga['id']);

        if ($leluhur !== null) {
            return redirect()->to('anggota/' . $leluhur->id)->with('info', 'Leluhur awal marga ' . $marga['nama'] . ' sudah ditetapkan.');
        }

        if ($this->request->is('post')) {
            $data = [];
            foreach ([
                'nama_lengkap', 'nama_panggilan', 'gelar_adat', 'tempat_lahir', 'tahun_lahir', 'tanggal_lahir',
                'status_hidup', 'tanggal_wafat', 'tahun_wafat', 'tempat_makam',
            ] as $k) {
                $data[$k] = $this->request->getPost($k);
            }
            $data['jenis_kelamin'] = 'L';

            try {
                $p = (new PersonService())->tambahLeluhurAwal((int) $marga['id'], array_filter($data, static fn ($v) => $v !== null), $this->user());
            } catch (ValidasiDataException $e) {
                return redirect()->back()->withInput()->with('kesalahan', $e->errors());
            } catch (SilsilahException $e) {
                return redirect()->back()->withInput()->with('galat', $e->getMessage());
            }

            return redirect()->to('anggota/' . $p->id)->with('sukses', 'Leluhur awal berhasil ditetapkan.');
        }

        return view('anggota/form', [
            'judul'    => 'Tetapkan Leluhur Awal Marga ' . $marga['nama'],
            'mode'     => 'leluhur',
            'induk'    => null,
            'person'   => null,
            'langsung' => true,
            'pasangan' => [],
            'aksi'     => site_url('admin/leluhur-awal'),
        ]);
    }
}
