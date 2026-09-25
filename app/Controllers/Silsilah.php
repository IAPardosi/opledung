<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonModel;
use App\Services\SilsilahQuery;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Silsilah as SilsilahConfig;

/**
 * Halaman publik: pohon silsilah umum dan daftar per generasi.
 */
class Silsilah extends BaseController
{
    public function pohon(?int $id = null): string
    {
        $persons = new PersonModel();
        $marga   = $this->margaAktif();

        if ($id === null) {
            $akar = $persons->leluhurAwal((int) $marga['id']);
        } else {
            $akar = $persons->find($id);
            if ($akar === null || ! in_array($akar->garis, ['utama', 'boru', 'anak_boru'], true)) {
                throw PageNotFoundException::forPageNotFound('Anggota tidak ditemukan.');
            }
        }

        $query = new SilsilahQuery();

        return view('silsilah/pohon', [
            'marga' => $marga,
            'akar'  => $akar,
            'jalur' => $akar === null ? [] : $query->jalurLeluhur($akar->id),
            'data'  => $akar === null ? null : $query->pohon($akar->id, config(SilsilahConfig::class)->kedalamanMuatPohon),
        ]);
    }

    public function generasi(): string
    {
        $marga    = $this->margaAktif();
        $generasi = $this->request->getGet('g');
        $generasi = is_numeric($generasi) ? (int) $generasi : null;
        $garis    = $this->request->getGet('garis') ?: null;
        $cari     = trim((string) $this->request->getGet('q'));

        $model = new PersonModel();
        $rows  = $model->daftar((int) $marga['id'], $generasi, $garis, $cari)->asArray()->paginate(50);

        return view('silsilah/generasi', [
            'marga'    => $marga,
            'rows'     => $rows,
            'pager'    => $model->pager,
            'rekap'    => (new SilsilahQuery())->rekapGenerasi((int) $marga['id']),
            'generasi' => $generasi,
            'garis'    => $garis,
            'cari'     => $cari,
        ]);
    }
}
