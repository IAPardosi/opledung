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

if (! function_exists('format_isi')) {
    /**
     * Format teks sederhana yang aman: paragraf, daftar "- ", **tebal**, *miring*, dan [teks](https://tautan).
     * Semua teks di-escape terlebih dahulu, jadi HTML dari pengguna tidak pernah dijalankan.
     */
    function format_isi(?string $teks): string
    {
        if ($teks === null || trim($teks) === '') {
            return '';
        }

        $html = '';
        foreach (preg_split('/\R{2,}/', trim($teks)) as $blok) {
            $baris = preg_split('/\R/', $blok);
            $daftar = array_filter($baris, static fn (string $b): bool => str_starts_with(ltrim($b), '- '));
            if (count($daftar) === count($baris)) {
                $html .= '<ul>' . implode('', array_map(static fn (string $b): string => '<li>' . format_baris(substr(ltrim($b), 2)) . '</li>', $baris)) . '</ul>';
            } else {
                $html .= '<p>' . implode('<br>', array_map('format_baris', $baris)) . '</p>';
            }
        }

        return $html;
    }

    function format_baris(string $baris): string
    {
        $baris = esc($baris);
        $baris = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $baris);
        $baris = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/', '<em>$1</em>', $baris);

        return preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/',
            static fn (array $m): string => '<a href="' . $m[2] . '" target="_blank" rel="noopener nofollow">' . $m[1] . '</a>',
            $baris,
        );
    }
}

if (! function_exists('tanggal_waktu_indo')) {
    function tanggal_waktu_indo(?string $waktu): string
    {
        if ($waktu === null || $waktu === '') {
            return '';
        }
        $jam = substr($waktu, 11, 5);

        return tanggal_indo($waktu) . ($jam !== '' && $jam !== '00:00' ? ', pukul ' . $jam . ' WIB' : '');
    }
}

if (! function_exists('nama_bulan')) {
    function nama_bulan(string $tanggal, bool $pendek = false): string
    {
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $nama  = $bulan[(int) substr($tanggal, 5, 2)];

        return $pendek ? mb_substr($nama, 0, 3) : $nama;
    }
}
