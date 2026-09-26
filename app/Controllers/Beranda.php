<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BeritaModel;
use App\Models\KegiatanModel;
use App\Models\PersonModel;
use App\Models\PunguanModel;
use App\Services\PartuturanService;
use App\Services\SilsilahQuery;

class Beranda extends BaseController
{
    public function index(): string
    {
        $marga   = $this->margaAktif();
        $query   = new SilsilahQuery();
        $persons = new PersonModel();
        $rekap   = $query->rekapGenerasi((int) $marga['id']);
        $user    = $this->user();

        // Kartu "Garis keturunan saya": jalur pengguna bila tertaut, selain itu contoh dari Sundut 1–3.
        $jalur = [];
        $tutur = null;
        if ($user?->person_id !== null && $user->can('silsilah.view')) {
            $jalur = $query->jalurLeluhur((int) $user->person_id);
            $saya  = end($jalur) ?: null;
            // Contoh partuturan: saudara ayah (amangtua/amanguda/namboru) atau saudara sendiri.
            $contoh = null;
            if ($saya?->induk_id) {
                $ayah   = $persons->find($saya->induk_id);
                $contoh = ($ayah ? $persons->saudaraKandung($ayah)[0] ?? null : null) ?? $persons->saudaraKandung($saya)[0] ?? null;
            }
            if ($contoh !== null) {
                $h     = (new PartuturanService())->hubungan($saya->id, $contoh->id);
                $tutur = $h ? ['sebutan' => $h['sebutan'], 'nama' => $contoh->nama_lengkap, 'generasi' => $contoh->generasi_ke] : null;
            }
        } elseif (($g1 = $persons->leluhurAwal((int) $marga['id'])) !== null) {
            $jalur = [$g1];
            for ($i = 0; $i < 2 && ($anak = $persons->where('garis', 'utama')->anak(end($jalur)->id)) !== []; $i++) {
                $jalur[] = $anak[0];
            }
        }

        $punguan = (new PunguanModel())->aktif((int) $marga['id']);
        $db      = db_connect();
        foreach ($punguan as &$p) {
            $p['jumlah'] = $db->table('users')->where('punguan_id', $p['id'])->where('person_id IS NOT NULL', null, false)->countAllResults();
        }
        unset($p);
        $punguanSaya = $user?->punguan_id ? array_values(array_filter($punguan, static fn ($p) => (int) $p['id'] === (int) $user->punguan_id))[0] ?? null : ($punguan[0] ?? null);

        $kegiatan = new KegiatanModel();
        $kegiatan->akanDatang();
        if ($punguanSaya !== null) {
            $kegiatan->groupStart()->where('punguan_id', $punguanSaya['id'])->orWhere('punguan_id', null)->groupEnd();
        }

        $aktif = array_values(array_filter($rekap, static fn (array $r): bool => $r['hidup'] > 0));

        return view('beranda', [
            'marga'       => $marga,
            'rekap'       => $rekap,
            'total'       => array_sum(array_map(static fn (array $r): int => $r['utama'] + $r['boru'], $rekap)),
            'aktif'       => $aktif === [] ? null : [$aktif[0]['generasi_ke'], end($aktif)['generasi_ke']],
            'jalur'       => $jalur,
            'jalurSaya'   => $user?->person_id !== null && $jalur !== [],
            'saudara'     => $query->jumlahSaudara($jalur),
            'tutur'       => $tutur,
            'punguan'     => $punguan,
            'punguanSaya' => $punguanSaya,
            'berita'      => (new BeritaModel())->terbit()->findAll(2),
            'kegiatan'    => $kegiatan->findAll(3),
        ]);
    }
}
