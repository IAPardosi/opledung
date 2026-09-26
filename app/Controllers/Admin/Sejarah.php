<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MargaModel;
use App\Services\AuditLogger;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Ketua Adat menulis kisah marga dan leluhur sebelum marga (informasi sejarah).
 */
class Sejarah extends BaseController
{
    public function index(): RedirectResponse|string
    {
        $marga = $this->margaAktif();

        if ($this->request->is('post')) {
            $nama = (array) $this->request->getPost('pra_nama');
            $ket  = (array) $this->request->getPost('pra_ket');
            $pra  = [];
            foreach ($nama as $i => $n) {
                if (trim((string) $n) !== '') {
                    $pra[] = ['nama' => mb_substr(trim((string) $n), 0, 150), 'keterangan' => mb_substr(trim((string) ($ket[$i] ?? '')), 0, 300)];
                }
            }

            $data = [
                'sejarah'      => trim((string) $this->request->getPost('sejarah')) ?: null,
                'asal_kampung' => trim((string) $this->request->getPost('asal_kampung')) ?: null,
                'pra_marga'    => $pra === [] ? null : json_encode(array_slice($pra, 0, 12), JSON_UNESCAPED_UNICODE),
            ];
            db_connect()->table('marga')->where('id', $marga['id'])->update($data);
            (new AuditLogger())->catat('ubah', 'marga', (int) $marga['id'], array_intersect_key($marga, $data), $data, $this->user()->id);

            return redirect()->to('admin/sejarah')->with('sukses', 'Kisah marga disimpan.');
        }

        return view('admin/sejarah', [
            'marga'    => $marga,
            'praMarga' => json_decode((string) ($marga['pra_marga'] ?? ''), true) ?: [],
        ]);
    }
}
