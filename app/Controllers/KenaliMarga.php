<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PersonModel;
use App\Services\SilsilahQuery;

/**
 * Kenali Marga (publik): kisah marga, leluhur sebelum marga (informasi sejarah,
 * di luar hitungan sundut), dan sundut-sundut awal Silsilah Pokok.
 */
class KenaliMarga extends BaseController
{
    public function index(): string
    {
        $marga   = $this->margaAktif();
        $persons = new PersonModel();
        $leluhur = $persons->leluhurAwal((int) $marga['id']);

        // Tiga sundut pertama sebagai kartu (G1, anak-anaknya, dan cucunya).
        $sundut = [];
        if ($leluhur !== null) {
            $sundut[1] = [$leluhur];
            foreach ([2, 3] as $g) {
                $sundut[$g] = [];
                foreach ($sundut[$g - 1] as $induk) {
                    array_push($sundut[$g], ...$persons->where('garis', 'utama')->anak($induk->id));
                }
            }
        }

        $user = $this->user();

        return view('kenali_marga', [
            'marga'    => $marga,
            'praMarga' => json_decode((string) ($marga['pra_marga'] ?? ''), true) ?: [],
            'sundut'   => $sundut,
            'rekap'    => (new SilsilahQuery())->rekapGenerasi((int) $marga['id']),
            'tertaut'  => $user?->person_id !== null,
        ]);
    }
}
