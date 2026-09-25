<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Partuturan as PartuturanPublik;
use App\Services\AuditLogger;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Ketua Adat menyesuaikan sebutan dan keterangan istilah partuturan.
 * Kunci istilah tetap (dipakai mesin partuturan).
 */
class Partuturan extends BaseController
{
    public function index(): RedirectResponse|string
    {
        $db   = db_connect();
        $rows = $db->table('partuturan')->orderBy('urutan')->get()->getResultArray();

        if ($this->request->is('post')) {
            $sebutan    = (array) $this->request->getPost('sebutan');
            $keterangan = (array) $this->request->getPost('keterangan');
            $diubah     = [];

            foreach ($rows as $r) {
                $s = trim((string) ($sebutan[$r['kunci']] ?? ''));
                $k = trim((string) ($keterangan[$r['kunci']] ?? ''));
                if ($s === '' || mb_strlen($s) > 100 || mb_strlen($k) > 255) {
                    continue;
                }
                if ($s !== $r['sebutan'] || $k !== (string) $r['keterangan']) {
                    $db->table('partuturan')->where('kunci', $r['kunci'])->update([
                        'sebutan' => $s, 'keterangan' => $k ?: null, 'updated_by' => $this->user()->id, 'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $diubah[$r['kunci']] = ['dari' => $r['sebutan'], 'menjadi' => $s];
                }
            }

            if ($diubah !== []) {
                (new AuditLogger())->catat('ubah', 'partuturan', null, null, $diubah, $this->user()->id);
            }

            return redirect()->to('admin/partuturan')->with('sukses', count($diubah) . ' istilah diperbarui.');
        }

        $grup = [];
        foreach ($rows as $r) {
            $grup[$r['kelompok']][] = $r;
        }

        return view('admin/partuturan', ['grup' => $grup, 'kelompok' => PartuturanPublik::KELOMPOK]);
    }
}
