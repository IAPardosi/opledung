<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Person;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\MarriageModel;
use App\Models\PersonModel;
use CodeIgniter\Shield\Entities\User;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Import massal anggota dari Excel/CSV (template: docs/template-import-anggota.csv).
 *
 * - kode_ref  : kode sementara baris di file (bebas, unik per file)
 * - kode_induk: kode_ref baris lain di file ATAU kode_anggota yang sudah ada di sistem;
 *               kosong berarti leluhur awal (Generasi 1)
 *
 * Import bersifat semua-atau-tidak-sama-sekali: bila ada satu baris gagal,
 * seluruh import dibatalkan. Mode pratinjau selalu dibatalkan di akhir.
 */
class ImportService
{
    public const KOLOM = [
        'kode_ref', 'kode_induk', 'nama_lengkap', 'nama_panggilan', 'gelar_adat', 'jenis_kelamin',
        'urutan_anak', 'nama_ibu', 'nama_pasangan', 'marga_pasangan', 'tempat_lahir', 'tanggal_lahir',
        'tahun_lahir', 'agama', 'status_perkawinan', 'pendidikan_terakhir', 'pekerjaan', 'golongan_darah',
        'kewarganegaraan', 'nik', 'no_kk', 'alamat_jalan', 'rt', 'rw', 'kode_wilayah', 'kode_pos',
        'no_hp', 'email', 'status_hidup', 'tanggal_wafat', 'tahun_wafat', 'tempat_makam',
    ];

    public const MAKS_BARIS = 20000;

    public function __construct(
        private readonly PersonService $service = new PersonService(),
        private readonly PersonModel $persons = new PersonModel(),
        private readonly MarriageModel $marriages = new MarriageModel(),
    ) {
    }

    /**
     * @return list<array<string, string>> baris data dengan kunci nama kolom
     */
    public function bacaFile(string $path, ?string $namaAsli = null): array
    {
        $ext = strtolower(pathinfo($namaAsli ?? $path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new SilsilahException('Format file harus .xlsx, .xls, atau .csv.');
        }

        $reader = IOFactory::createReader(match ($ext) {
            'xlsx'  => 'Xlsx',
            'xls'   => 'Xls',
            default => 'Csv',
        });
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        $rows   = $sheet->toArray(null, true, false, false);
        $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), array_shift($rows) ?? []);

        $kurang = array_diff(['kode_ref', 'kode_induk', 'nama_lengkap', 'jenis_kelamin'], $header);
        if ($kurang !== []) {
            throw new SilsilahException('Kolom wajib tidak ditemukan: ' . implode(', ', $kurang) . '. Gunakan template import.');
        }

        $hasil = [];
        foreach ($rows as $row) {
            $row = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            if (implode('', array_map(static fn ($v): string => trim((string) $v), $row)) === '') {
                continue;
            }
            $hasil[] = $row;
        }

        if (count($hasil) > self::MAKS_BARIS) {
            throw new SilsilahException('Maksimal ' . self::MAKS_BARIS . ' baris per import. Pecah file menjadi beberapa bagian.');
        }

        return $hasil;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{berhasil: bool, disimpan: bool, laporan: list<array<string, mixed>>, jumlah_ok: int, jumlah_gagal: int}
     */
    public function proses(int $margaId, array $rows, ?User $aktor, bool $simpan): array
    {
        $db = Database::connect();
        $db->transException(true)->transBegin();

        /** @var array<string, int> $peta kode_ref → id person */
        $peta    = [];
        $laporan = [];
        $gagal   = 0;

        foreach ($rows as $i => $row) {
            $baris = $i + 2; // baris 1 adalah header
            $ref   = trim((string) ($row['kode_ref'] ?? ''));
            $nama  = trim((string) ($row['nama_lengkap'] ?? ''));

            try {
                if ($ref !== '' && isset($peta[$ref])) {
                    throw new SilsilahException("kode_ref '{$ref}' dipakai lebih dari sekali.");
                }

                $person = $this->prosesBaris($margaId, $row, $peta, $aktor);
                if ($ref !== '') {
                    $peta[$ref] = $person->id;
                }
                $laporan[] = ['baris' => $baris, 'kode_ref' => $ref, 'nama' => $nama, 'ok' => true, 'pesan' => '', 'kode_anggota' => $person->kode_anggota, 'generasi' => $person->generasi_ke];
            } catch (ValidasiDataException $e) {
                $gagal++;
                $laporan[] = ['baris' => $baris, 'kode_ref' => $ref, 'nama' => $nama, 'ok' => false, 'pesan' => implode(' ', $e->errors())];
            } catch (SilsilahException $e) {
                $gagal++;
                $laporan[] = ['baris' => $baris, 'kode_ref' => $ref, 'nama' => $nama, 'ok' => false, 'pesan' => $e->getMessage()];
            } catch (Throwable $e) {
                $db->transRollback();

                throw $e;
            }
        }

        $disimpan = $simpan && $gagal === 0;
        $disimpan ? $db->transCommit() : $db->transRollback();

        return [
            'berhasil'     => $gagal === 0,
            'disimpan'     => $disimpan,
            'laporan'      => $laporan,
            'jumlah_ok'    => count($laporan) - $gagal,
            'jumlah_gagal' => $gagal,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int>   $peta
     */
    private function prosesBaris(int $margaId, array $row, array $peta, ?User $aktor): Person
    {
        $data  = $this->petakanData($row);
        $induk = trim((string) ($row['kode_induk'] ?? ''));

        if ($induk === '') {
            $person = $this->service->tambahLeluhurAwal($margaId, $data, $aktor);
        } else {
            $indukId = $peta[$induk] ?? $this->cariKode($margaId, $induk);
            if ($indukId === null) {
                throw new SilsilahException("Induk '{$induk}' tidak ditemukan (harus kode_ref baris sebelumnya atau kode anggota yang sudah ada).");
            }

            // Bila induk tepat punya satu pasangan, pasangan itu otomatis menjadi ibu/ayah anak.
            $pasangan = $this->marriages->pasanganIds($indukId);
            if (count($pasangan) === 1) {
                $data['pasangan_id'] = $pasangan[0];
            }

            $person = $this->service->tambahAnak($indukId, $data, $aktor);
        }

        $namaPasangan = trim((string) ($row['nama_pasangan'] ?? ''));
        if ($namaPasangan !== '') {
            $this->service->tambahPasangan($person->id, [
                'nama_lengkap' => $namaPasangan,
                'marga_nama'   => trim((string) ($row['marga_pasangan'] ?? '')),
                'status_hidup' => 'tidak_diketahui',
            ], [], $aktor);
        }

        return $person;
    }

    private function cariKode(int $margaId, string $kode): ?int
    {
        $p = $this->persons->select('id')->where('marga_id', $margaId)->where('kode_anggota', $kode)->first();

        return $p?->id;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function petakanData(array $row): array
    {
        $ambil = static fn (string $k): string => trim((string) ($row[$k] ?? ''));

        $data = [];
        foreach ([
            'nama_lengkap', 'nama_panggilan', 'gelar_adat', 'urutan_anak', 'nama_ibu', 'tempat_lahir',
            'tahun_lahir', 'agama', 'status_perkawinan', 'pendidikan_terakhir', 'pekerjaan', 'golongan_darah',
            'kewarganegaraan', 'nik', 'no_kk', 'alamat_jalan', 'rt', 'rw', 'kode_pos', 'no_hp', 'email',
            'tahun_wafat', 'tempat_makam',
        ] as $k) {
            if ($ambil($k) !== '') {
                $data[$k] = $ambil($k);
            }
        }

        $jk = strtoupper(substr($ambil('jenis_kelamin'), 0, 1));
        $data['jenis_kelamin'] = in_array($jk, ['L', 'P'], true) ? $jk : $ambil('jenis_kelamin');

        foreach (['tanggal_lahir', 'tanggal_wafat'] as $k) {
            if (($tgl = $this->tanggal($row[$k] ?? null)) !== null) {
                $data[$k] = $tgl;
            }
        }

        if ($ambil('status_hidup') !== '') {
            $data['status_hidup'] = str_replace(' ', '_', strtolower($ambil('status_hidup')));
        }

        if ($ambil('kode_wilayah') !== '') {
            $data['desa_kode'] = $ambil('kode_wilayah');
        }

        // NIK/No. KK/No. HP sering terbaca sebagai angka oleh Excel; kembalikan ke teks.
        foreach (['nik', 'no_kk'] as $k) {
            if (isset($data[$k]) && is_numeric($data[$k])) {
                $data[$k] = number_format((float) $data[$k], 0, '', '');
            }
        }
        if (isset($data['no_hp']) && preg_match('/^8\d+$/', $data['no_hp'])) {
            $data['no_hp'] = '0' . $data['no_hp'];
        }

        return $data;
    }

    /**
     * Menerima YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, atau nomor tanggal Excel.
     */
    private function tanggal(mixed $nilai): ?string
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }
        if (is_numeric($nilai) && (float) $nilai > 1000 && (float) $nilai < 100000) {
            return ExcelDate::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
        }

        $nilai = trim((string) $nilai);
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $nilai, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return $nilai; // divalidasi oleh PersonService (format Y-m-d)
    }
}
