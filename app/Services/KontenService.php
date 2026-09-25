<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;

/**
 * Bantuan untuk berita dan kegiatan: slug unik dan gambar sampul.
 */
class KontenService
{
    public const DIR_GAMBAR = FCPATH . 'uploads/konten/';

    private readonly BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function slugUnik(string $tabel, string $judul, ?int $abaikanId = null): string
    {
        helper('text');
        $dasar = mb_substr(url_title(convert_accented_characters($judul), '-', true), 0, 200) ?: 'konten';
        $slug  = $dasar;
        $n     = 2;

        while (true) {
            $q = $this->db->table($tabel)->where('slug', $slug);
            if ($abaikanId !== null) {
                $q->where('id !=', $abaikanId);
            }
            if ($q->countAllResults() === 0) {
                return $slug;
            }
            $slug = $dasar . '-' . $n++;
        }
    }

    /**
     * Gambar diubah ulang ke JPEG (maks. 1600px) sehingga metadata dan isi berbahaya terbuang.
     */
    public function simpanGambar(UploadedFile $file, ?string $lama = null): string
    {
        if (! is_dir(self::DIR_GAMBAR)) {
            mkdir(self::DIR_GAMBAR, 0755, true);
        }

        $nama = date('Ym') . '-' . bin2hex(random_bytes(8)) . '.jpg';
        service('image')->withFile($file->getTempName())
            ->resize(1600, 1600, true)
            ->convert(IMAGETYPE_JPEG)
            ->save(self::DIR_GAMBAR . $nama, 82);

        $this->hapusGambar($lama);

        return $nama;
    }

    public function hapusGambar(?string $nama): void
    {
        if ($nama !== null && $nama !== '' && is_file(self::DIR_GAMBAR . basename($nama))) {
            unlink(self::DIR_GAMBAR . basename($nama));
        }
    }
}
