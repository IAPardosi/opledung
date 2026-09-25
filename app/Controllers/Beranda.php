<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonModel;
use App\Services\SilsilahQuery;

class Beranda extends BaseController
{
    public function index(): string
    {
        $marga   = $this->margaAktif();
        $rekap   = (new SilsilahQuery())->rekapGenerasi((int) $marga['id']);
        $leluhur = (new PersonModel())->leluhurAwal((int) $marga['id']);

        $aktif = array_values(array_filter($rekap, static fn (array $r): bool => $r['hidup'] > 0));

        return view('beranda', [
            'marga'   => $marga,
            'rekap'   => $rekap,
            'leluhur' => $leluhur,
            'total'   => array_sum(array_map(static fn (array $r): int => $r['utama'] + $r['boru'], $rekap)),
            'hidup'   => array_sum(array_column($rekap, 'hidup')),
            'maks'    => max([1, ...array_map(static fn (array $r): int => $r['utama'] + $r['boru'], $rekap)]),
            'aktif'   => $aktif === [] ? null : [$aktif[0]['generasi_ke'], end($aktif)['generasi_ke']],
        ]);
    }
}
