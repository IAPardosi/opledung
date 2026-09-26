<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Exceptions\SilsilahException;
use App\Models\KeanggotaanPunguanModel;
use App\Models\PunguanModel;
use App\Services\LingkupAdmin;
use App\Services\PersonService;
use App\Services\PunguanService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;

/**
 * Data Anggota: admin mengontrol status seluruh anggota (hidup/meninggal/tidak diketahui)
 * dan melihat jenis keanggotaannya (Member Marga / Member Punguan) serta akun webnya.
 */
class Anggota extends BaseController
{
    public function index(): string
    {
        $marga = $this->margaAktif();
        $f     = [
            'q'       => trim((string) $this->request->getGet('q')),
            'g'       => is_numeric($this->request->getGet('g')) ? (int) $this->request->getGet('g') : null,
            'hidup'   => in_array($this->request->getGet('hidup'), ['hidup', 'meninggal', 'tidak_diketahui'], true) ? $this->request->getGet('hidup') : null,
            'member'  => in_array($this->request->getGet('member'), ['punguan', 'marga', 'menunggu'], true) ? $this->request->getGet('member') : null,
            'akun'    => in_array($this->request->getGet('akun'), ['ada', 'tidak'], true) ? $this->request->getGet('akun') : null,
            'punguan' => (int) $this->request->getGet('punguan') ?: null,
            'garis'   => in_array($this->request->getGet('garis'), ['utama', 'boru', 'anak_boru', 'pasangan'], true) ? $this->request->getGet('garis') : null,
        ];

        $db = Database::connect();
        $b  = $db->table('persons')
            ->select("persons.id, persons.kode_anggota, persons.nama_lengkap, persons.generasi_ke, persons.garis, persons.jenis_kelamin,
                persons.status_hidup, persons.tanggal_wafat, persons.tahun_wafat, persons.tempat_makam, persons.status_data,
                persons.tahun_lahir, persons.tanggal_lahir, users.id AS user_id, users.username, users.active AS akun_aktif,
                kp.status AS status_punguan, pg.nama AS nama_punguan", false)
            ->join('users', 'users.person_id = persons.id AND users.deleted_at IS NULL', 'left', false)
            ->join('keanggotaan_punguan kp', "kp.person_id = persons.id AND kp.status IN ('aktif','menunggu')", 'left', false)
            ->join('punguan pg', 'pg.id = kp.punguan_id', 'left')
            ->where('persons.marga_id', (int) $marga['id'])
            ->where('persons.deleted_at', null);
        (new LingkupAdmin())->saringPerson($b, $this->user());

        if ($f['q'] !== '') {
            $b->groupStart()->like('persons.nama_lengkap', $f['q'])->orLike('persons.kode_anggota', $f['q'])->orLike('users.username', $f['q'])->groupEnd();
        }
        if ($f['g'] !== null) {
            $b->where('persons.generasi_ke', $f['g']);
        }
        if ($f['hidup'] !== null) {
            $b->where('persons.status_hidup', $f['hidup']);
        }
        if ($f['garis'] !== null) {
            $b->where('persons.garis', $f['garis']);
        }
        match ($f['member']) {
            'punguan'  => $b->where('kp.status', 'aktif'),
            'menunggu' => $b->where('kp.status', 'menunggu'),
            'marga'    => $b->where('kp.id', null),
            default    => null,
        };
        if ($f['akun'] === 'ada') {
            $b->where('users.id IS NOT NULL', null, false);
        } elseif ($f['akun'] === 'tidak') {
            $b->where('users.id', null);
        }
        if ($f['punguan'] !== null) {
            $b->where('kp.punguan_id', $f['punguan']);
        }

        $total   = (clone $b)->countAllResults(false);
        $halaman = max(1, (int) $this->request->getGet('hal'));
        $rows    = $b->orderBy('persons.generasi_ke', 'DESC')->orderBy('persons.nama_lengkap')
            ->limit(50, ($halaman - 1) * 50)->get()->getResultArray();

        $rekap = $db->table('persons')->select('status_hidup, COUNT(*) AS n')
            ->where('marga_id', (int) $marga['id'])->where('deleted_at', null)->groupBy('status_hidup');
        (new LingkupAdmin())->saringPerson($rekap, $this->user());

        return view('admin/anggota_index', [
            'rows'     => $rows,
            'f'        => $f,
            'total'    => $total,
            'halaman'  => $halaman,
            'rekap'    => array_map('intval', array_column($rekap->get()->getResultArray(), 'n', 'status_hidup')),
            'member'   => (new KeanggotaanPunguanModel())->select('status, COUNT(*) AS n')->whereIn('status', ['aktif', 'menunggu'])->groupBy('status')->findAll(),
            'punguan'  => (new PunguanModel())->aktif((int) $marga['id']),
            'generasi' => array_column($db->table('persons')->select('DISTINCT generasi_ke', false)->where('marga_id', (int) $marga['id'])->orderBy('generasi_ke')->get()->getResultArray(), 'generasi_ke'),
        ]);
    }

    /**
     * Mengubah status hidup seseorang. Bila ditandai meninggal, keanggotaan punguannya diakhiri.
     */
    public function status(int $id): RedirectResponse
    {
        $status = (string) $this->request->getPost('status_hidup');
        if (! in_array($status, ['hidup', 'meninggal', 'tidak_diketahui'], true)) {
            return redirect()->back()->with('galat', 'Status tidak dikenal.');
        }

        $data = ['status_hidup' => $status];
        if ($status === 'meninggal') {
            $tanggal = $this->request->getPost('tanggal_wafat') ?: null;
            $tahun   = $this->request->getPost('tahun_wafat') ?: null;
            if ($tanggal !== null) {
                $data['tanggal_wafat'] = $tanggal;
            } elseif ($tahun !== null) {
                $data['tahun_wafat'] = (int) $tahun;
            }
            $data['tempat_makam'] = trim((string) $this->request->getPost('tempat_makam')) ?: null;
        } else {
            $data += ['tanggal_wafat' => null, 'tahun_wafat' => null, 'tempat_makam' => null];
        }

        try {
            $person = (new PersonService())->ubahProfil($id, $data, $this->user());
            $pesan  = $person->nama_lengkap . ' ditandai ' . ['hidup' => 'hidup', 'meninggal' => 'meninggal dunia', 'tidak_diketahui' => 'tidak diketahui statusnya'][$status] . '.';
            if ($status === 'meninggal' && (new PunguanService())->akhiriKarenaMeninggal($id, $this->user(), $person->tanggal_wafat)) {
                $pesan .= ' Keanggotaan punguannya diakhiri otomatis.';
            }

            return redirect()->back()->with('sukses', $pesan);
        } catch (SilsilahException $e) {
            return redirect()->back()->with('galat', $e->getMessage());
        }
    }
}
