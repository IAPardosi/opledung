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

    /**
     * Jalur saya: garis lurus dari leluhur sebelum marga dan Sundut 1 sampai orang ini.
     */
    public function garis(?int $id = null): string
    {
        $id ??= $this->user()?->person_id !== null ? (int) $this->user()->person_id : null;
        if ($id === null) {
            return view('silsilah/belum_tertaut', ['mode' => 'garis']);
        }

        $query  = new SilsilahQuery();
        $jalur  = $query->jalurLeluhur($id);
        $person = end($jalur) ?: null;
        if ($person === null || $person->garis === 'pasangan') {
            throw PageNotFoundException::forPageNotFound('Anggota tidak ditemukan.');
        }

        $marga = (new \App\Models\MargaModel())->find($person->marga_id);

        return view('silsilah/garis', [
            'person'  => $person,
            'jalur'   => $jalur,
            'saudara' => $query->jumlahSaudara($jalur),
            'marga'   => $marga,
            'praMarga'=> json_decode((string) ($marga['pra_marga'] ?? ''), true) ?: [],
            'diri'    => $this->user()?->person_id !== null && (int) $this->user()->person_id === $person->id,
        ]);
    }

    /**
     * Keluarga dekat: orang ini di tengah, 2 sundut ke atas dan ke bawah.
     */
    public function keluargaDekat(?int $id = null): string
    {
        $user = $this->user();
        $id ??= $user?->person_id !== null ? (int) $user->person_id : null;
        if ($id === null) {
            return view('silsilah/belum_tertaut', ['mode' => 'keluarga']);
        }

        $data = (new SilsilahQuery())->keluargaDekat($id);
        if ($data === null) {
            throw PageNotFoundException::forPageNotFound('Anggota tidak ditemukan.');
        }

        // Partuturan pengguna kepada setiap orang yang tampil.
        $tutur = [];
        if ($user?->person_id !== null) {
            $mesin = new \App\Services\PartuturanService();
            $orang = array_filter([
                $data['ompung'], $data['orangTua'], $data['person'],
                ...$data['saudaraOrangTua'], ...$data['saudara'], ...$data['anak'], ...$data['pahompu'],
            ]);
            foreach ([...$data['ompungPasangan'], ...$data['orangTuaPasangan'], ...$data['pasangan']] as $ps) {
                $orang[] = (object) ['id' => (int) $ps['id']];
            }
            foreach ($orang as $o) {
                if (! isset($tutur[$o->id]) && (int) $o->id !== (int) $user->person_id) {
                    $h = $mesin->hubungan((int) $user->person_id, (int) $o->id);
                    $tutur[$o->id] = $h['sebutan'] ?? null;
                }
            }
        }

        return view('silsilah/keluarga_dekat', [...$data, 'tutur' => $tutur, 'sayaId' => $user?->person_id !== null ? (int) $user->person_id : null]);
    }
}
