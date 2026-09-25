<?php

declare(strict_types=1);

use App\Entities\Person;

if (! function_exists('label_garis')) {
    function label_garis(string $garis): string
    {
        return config('Silsilah')->garis[$garis] ?? $garis;
    }
}

if (! function_exists('badge_garis')) {
    function badge_garis(string $garis): string
    {
        return '<span class="badge badge-garis garis-' . esc($garis, 'attr') . '">' . esc(label_garis($garis)) . '</span>';
    }
}

if (! function_exists('tanggal_indo')) {
    /**
     * 2026-09-25 → 25 September 2026
     */
    function tanggal_indo(?string $tanggal): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '';
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        [$y, $m, $d] = array_map('intval', explode('-', substr($tanggal, 0, 10)));

        return $d . ' ' . $bulan[$m] . ' ' . $y;
    }
}

if (! function_exists('lahir_wafat')) {
    /**
     * Rentang hidup ringkas, mis. "1950 – 2020" atau "l. 1985".
     */
    function lahir_wafat(Person|array $p): string
    {
        $p     = $p instanceof Person ? $p->toRawArray() : $p;
        $lahir = $p['tahun_lahir'] ?? null;
        $wafat = $p['tahun_wafat'] ?? null;

        if (($p['status_hidup'] ?? '') === 'meninggal') {
            return ($lahir ?: '?') . ' – ' . ($wafat ?: '?');
        }

        return $lahir ? 'l. ' . $lahir : '';
    }
}

if (! function_exists('inisial')) {
    function inisial(string $nama): string
    {
        $kata = preg_split('/[^\p{L}]+/u', $nama, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(mb_substr($kata[0] ?? '', 0, 1) . mb_substr($kata[1] ?? '', 0, 1));
    }
}
