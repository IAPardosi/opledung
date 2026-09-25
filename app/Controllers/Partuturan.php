<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonModel;
use App\Services\PartuturanService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Partuturan: kamus istilah (publik) dan cek hubungan antar anggota (member).
 */
class Partuturan extends BaseController
{
    public const KELOMPOK = [
        'orang_tua'  => 'Orang tua',
        'ompung'     => 'Ompung & leluhur',
        'paman_bibi' => 'Setingkat orang tua',
        'sesundut'   => 'Sesundut (satu generasi)',
        'keponakan'  => 'Satu sundut di bawah',
        'keturunan'  => 'Keturunan',
        'perkawinan' => 'Karena perkawinan',
        'lainnya'    => 'Lainnya',
    ];

    public function kamus(): string
    {
        $istilah = db_connect()->table('partuturan')->where('kunci !=', 'diri')->orderBy('urutan')->get()->getResultArray();
        $grup    = [];
        foreach ($istilah as $i) {
            $grup[$i['kelompok']][] = $i;
        }

        return view('partuturan/kamus', ['grup' => $grup]);
    }

    public function hubungan(): string
    {
        $persons = new PersonModel();
        $user    = $this->user();
        $dariId  = (int) ($this->request->getGet('dari') ?: $user->person_id);
        $keId    = (int) $this->request->getGet('ke');

        $dari  = $dariId ? $persons->find($dariId) : null;
        $ke    = $keId ? $persons->find($keId) : null;
        $hasil = $dari && $ke ? (new PartuturanService())->hubungan($dari->id, $ke->id) : null;

        return view('partuturan/hubungan', [
            'dari'   => $dari,
            'ke'     => $ke,
            'hasil'  => $hasil,
            'diri'   => $dari !== null && $user->person_id !== null && (int) $user->person_id === $dari->id,
            'tautan' => $user->person_id !== null,
        ]);
    }

    /**
     * Panggilan pengguna yang login kepada orang ini (untuk panel pohon dan pencarian).
     */
    public function api(int $id): ResponseInterface
    {
        $user = $this->user();
        if ($user->person_id === null) {
            return $this->response->setJSON(['tersedia' => false, 'pesan' => 'Tautkan akun Anda ke data silsilah untuk melihat partuturan.']);
        }

        $h = (new PartuturanService())->hubungan((int) $user->person_id, $id);
        if ($h === null) {
            return $this->response->setJSON(['tersedia' => false, 'pesan' => 'Hubungan tidak ditemukan dalam silsilah.']);
        }

        return $this->response->setJSON([
            'tersedia'   => true,
            'sebutan'    => $h['sebutan'],
            'keterangan' => $h['keterangan'],
            'balik'      => $h['balik']['sebutan'],
            'rincian'    => $h['rincian'],
        ]);
    }
}
