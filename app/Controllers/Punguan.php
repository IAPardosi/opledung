<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\KegiatanModel;
use App\Models\PunguanModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Halaman publik punguan (organisasi pomparan per daerah).
 */
class Punguan extends BaseController
{
    public function index(): string
    {
        $marga = $this->margaAktif();
        $rows  = (new PunguanModel())->aktif((int) $marga['id']);
        $db    = db_connect();

        foreach ($rows as &$p) {
            $p['jumlah'] = $db->table('users')->where('punguan_id', $p['id'])->where('person_id IS NOT NULL', null, false)->countAllResults();
        }
        unset($p);

        return view('punguan/index', ['marga' => $marga, 'rows' => $rows]);
    }

    public function detail(string $slug): string
    {
        $p = (new PunguanModel())->where('slug', $slug)->where('is_active', 1)->first();
        if ($p === null) {
            throw PageNotFoundException::forPageNotFound('Punguan tidak ditemukan.');
        }

        return view('punguan/detail', [
            'p'        => $p,
            'jumlah'   => db_connect()->table('users')->where('punguan_id', $p['id'])->where('person_id IS NOT NULL', null, false)->countAllResults(),
            'kegiatan' => (new KegiatanModel())->akanDatang()->where('punguan_id', $p['id'])->findAll(10),
            'penatua'  => db_connect()->table('admin_lingkup l')->select('u.username, pr.nama_lengkap')
                ->join('users u', 'u.id = l.user_id')->join('persons pr', 'pr.id = u.person_id', 'left')
                ->where(['l.jenis' => 'punguan', 'l.nilai' => (string) $p['id']])->get()->getResultArray(),
        ]);
    }
}
