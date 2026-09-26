<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/**
 * Memilih punguan aktif di halaman pengurus: ?punguan=ID, punguan akun, atau yang pertama.
 */
trait PilihPunguan
{
    /**
     * @param list<array<string, mixed>> $daftar
     *
     * @return array<string, mixed>|null
     */
    private function pilihPunguan(array $daftar): ?array
    {
        $diminta = (int) $this->request->getGet('punguan');
        foreach ($daftar as $p) {
            if ((int) $p['id'] === $diminta) {
                return $p;
            }
        }
        foreach ($daftar as $p) {
            if ((int) $p['id'] === (int) $this->user()->punguan_id) {
                return $p;
            }
        }

        return $daftar[0] ?? null;
    }
}
