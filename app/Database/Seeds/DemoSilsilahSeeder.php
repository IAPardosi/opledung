<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Entities\Person;
use App\Models\MargaModel;
use App\Models\UserModel;
use App\Services\LingkupAdmin;
use App\Services\PersonService;
use App\Services\UsulanService;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use Faker\Factory;
use Faker\Generator;
use RuntimeException;

/**
 * Data CONTOH silsilah marga Pardosi untuk development dan uji performa.
 * Semua nama dan data pribadi adalah fiktif.
 *
 * php spark db:seed DemoSilsilahSeeder
 *
 * Ukuran dapat diatur lewat .env: demo.generasi (bawaan 14),
 * demo.maksPenerusPerGenerasi (bawaan 120).
 */
class DemoSilsilahSeeder extends Seeder
{
    private const NAMA_LAKI = [
        'Hotman', 'Sahat', 'Tigor', 'Parulian', 'Marihot', 'Binsar', 'Togar', 'Mangasi', 'Poltak', 'Jonggi',
        'Bonar', 'Hasudungan', 'Pangihutan', 'Maruli', 'Horas', 'Tumpal', 'Gomgom', 'Sabar', 'Halomoan', 'Martua',
        'Rudi', 'Daniel', 'Samuel', 'Yosua', 'Johannes', 'Andreas', 'Michael', 'Kevin', 'Gabriel', 'Nathan',
    ];
    private const NAMA_PEREMPUAN = [
        'Rosmawati', 'Tiurma', 'Nurmaida', 'Lamria', 'Hotmaida', 'Rotua', 'Dameria', 'Sondang', 'Tiominar', 'Marta',
        'Ruth', 'Maria', 'Grace', 'Christina', 'Debora', 'Yohana', 'Angelina', 'Lestari', 'Sarah', 'Agnes',
    ];
    private const MARGA_LAIN = [
        'Simanjuntak', 'Sitompul', 'Nainggolan', 'Siregar', 'Hutagalung', 'Situmorang', 'Sinaga', 'Pasaribu',
        'Silalahi', 'Manurung', 'Sihombing', 'Panjaitan', 'Simatupang', 'Lumbantobing', 'Tambunan', 'Hutapea',
    ];
    private const PEKERJAAN = [
        'Petani/Pekebun', 'Wiraswasta', 'Karyawan Swasta', 'Pegawai Negeri Sipil', 'Guru', 'Dosen',
        'Pedagang', 'Mengurus Rumah Tangga', 'Pelajar/Mahasiswa', 'Dokter', 'Perawat', 'Pendeta',
    ];

    private PersonService $service;
    private Generator $faker;
    private int $tahunG1;

    /**
     * @var list<string>
     */
    private array $desa = [];

    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('DemoSilsilahSeeder tidak boleh dijalankan di production.');
        }

        $marga = (new MargaModel())->where('kode', 'PDS')->first();
        if ($marga === null) {
            throw new RuntimeException('Jalankan DatabaseSeeder terlebih dahulu.');
        }
        if ($this->db->table('persons')->where('marga_id', $marga['id'])->countAllResults() > 0) {
            echo '  Data silsilah sudah ada, seeder contoh dilewati.' . PHP_EOL;

            return;
        }

        mt_srand(20260925);
        $this->faker = Factory::create('id_ID');
        $this->faker->seed(20260925);
        $this->service = new PersonService();

        $jumlahGenerasi = (int) env('demo.generasi', 14);
        $maksPenerus    = (int) env('demo.maksPenerusPerGenerasi', 120);
        $this->tahunG1  = 2012 - ($jumlahGenerasi - 1) * 27;

        $this->desa = array_column(
            $this->db->table('wilayah')->select('kode')->where('tingkat', 4)
                ->groupStart()->like('kode', '12.', 'after')->orLike('kode', '31.', 'after')->orLike('kode', '14.', 'after')->groupEnd()
                ->orderBy('RAND(20260925)', '', false)->limit(400)->get()->getResultArray(),
            'kode',
        );

        $mulai = microtime(true);

        $leluhur = $this->service->tambahLeluhurAwal((int) $marga['id'], [
            'nama_lengkap'  => 'Op. Dongan',
            'gelar_adat'    => null,
            'jenis_kelamin' => 'L',
            'tahun_lahir'   => $this->tahunG1,
            'status_hidup'  => 'meninggal',
            'biografi'      => 'Leluhur awal marga Pardosi (Sundut 1). Kisah lengkapnya dituliskan Ketua Adat; teks ini contoh.',
        ], null);

        $penerus = [$leluhur];
        $contohMember = null;

        for ($g = 2; $g <= $jumlahGenerasi && $penerus !== []; $g++) {
            $berikut = [];
            foreach ($penerus as $ayah) {
                $istri = $this->service->tambahPasangan($ayah->id, $this->dataOrang('P', $g - 1, true), [], null);

                $jumlahAnak = mt_rand(1, 5);
                for ($i = 0; $i < $jumlahAnak; $i++) {
                    $jk   = ($i === 0 || mt_rand(0, 1) === 1) ? 'L' : 'P';
                    $data = $this->dataOrang($jk, $g);
                    // Garis awal sesuai contoh: Op. Dongan → Op. Ledung → Paedang.
                    if ($i === 0 && $g === 2) {
                        $data = [...$data, 'nama_lengkap' => 'Op. Ledung', 'biografi' => 'Sundut 2, anak sulung Op. Dongan (teks contoh).'];
                    } elseif ($i === 0 && $g === 3 && $ayah->nama_lengkap === 'Op. Ledung') {
                        $data = [...$data, 'nama_lengkap' => 'Paedang', 'biografi' => 'Sundut 3, anak sulung Op. Ledung (teks contoh).'];
                    }
                    $anak = $this->service->tambahAnak($ayah->id, [...$data, 'pasangan_id' => $istri->id], null);

                    if ($jk === 'L') {
                        $berikut[] = $anak;
                        if ($g === 12 && $contohMember === null) {
                            $contohMember = $anak;
                        }
                    } elseif ($g < $jumlahGenerasi && mt_rand(1, 3) === 1) {
                        $this->isiKeluargaBoru($anak, $g);
                    }
                }
            }

            shuffle($berikut);
            $penerus = array_slice($berikut, 0, $maksPenerus);
            echo "  Generasi {$g}: " . count($berikut) . ' anak laki-laki' . PHP_EOL;
        }

        // Silsilah Pokok contoh dikunci seolah sudah divalidasi Ketua Adat.
        $this->db->table('persons')
            ->where('marga_id', $marga['id'])
            ->where('generasi_ke <=', $marga['batas_silsilah_pokok'])
            ->update(['status_data' => 'terkunci']);

        $this->db->table('marga')->where('id', $marga['id'])->update([
            'asal_kampung' => '[Nama huta/bona pasogit] (contoh)',
            'sejarah'      => "Teks ini contoh. Ketua Adat menuliskan kisah marga Pardosi di sini: asal-usul, bona pasogit, tugu, dan pesan para leluhur.\n\nSetiap pomparan dapat menelusuri jalurnya dari Op. Dongan sampai sundut sekarang.",
            'pra_marga'    => json_encode([
                ['nama' => '[Leluhur sebelum marga 1]', 'keterangan' => 'Contoh: diisi Ketua Adat'],
                ['nama' => '[Leluhur sebelum marga 2]', 'keterangan' => ''],
                ['nama' => '[Leluhur sebelum marga 3]', 'keterangan' => ''],
                ['nama' => '[Leluhur sebelum marga 4]', 'keterangan' => ''],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->buatAkunDemo((int) $marga['id'], $contohMember);
        $this->call(DemoKontenSeeder::class);

        $total = $this->db->table('persons')->where('marga_id', $marga['id'])->countAllResults();
        printf('  Total %d orang dalam %.1f detik.%s', $total, microtime(true) - $mulai, PHP_EOL);
    }

    /**
     * Boru dicatat bersama suami dan anak-anaknya (ujung cabang).
     */
    private function isiKeluargaBoru(Person $boru, int $generasi): void
    {
        $suami = $this->service->tambahPasangan($boru->id, $this->dataOrang('L', $generasi, true), [], null);
        for ($i = 0, $n = mt_rand(1, 3); $i < $n; $i++) {
            $jk   = mt_rand(0, 1) ? 'L' : 'P';
            $data = $this->dataOrang($jk, $generasi + 1, false, true);
            // Anak boru memakai marga ayahnya (suami boru).
            $data['nama_lengkap'] = strtok($data['nama_lengkap'], ' ') . ($jk === 'P' ? ' br. ' : ' ') . $suami->marga_nama;
            $this->service->tambahAnak($boru->id, [...$data, 'pasangan_id' => $suami->id], null);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dataOrang(string $jk, int $generasi, bool $pasangan = false, bool $ringkas = false): array
    {
        $tahun = $this->tahunG1 + ($generasi - 1) * 27 + mt_rand(-4, 6);
        $hidup = $tahun >= 1945 || ($tahun >= 1935 && mt_rand(0, 1));
        $nama  = $jk === 'L'
            ? self::NAMA_LAKI[array_rand(self::NAMA_LAKI)]
            : self::NAMA_PEREMPUAN[array_rand(self::NAMA_PEREMPUAN)];

        $margaNama = $pasangan ? self::MARGA_LAIN[array_rand(self::MARGA_LAIN)] : null;
        $data      = [
            'nama_lengkap'  => $pasangan
                ? $nama . ($jk === 'P' ? ' br. ' : ' ') . $margaNama
                : $nama . ($jk === 'P' ? ' br. Pardosi' : ' Pardosi'),
            'jenis_kelamin' => $jk,
            'marga_nama'    => $margaNama,
            'status_hidup'  => $hidup ? 'hidup' : 'meninggal',
        ];

        if ($ringkas) {
            return [...$data, 'tahun_lahir' => min($tahun, 2025)];
        }

        if (! $hidup || $tahun < 1950) {
            return [...$data, 'tahun_lahir' => $tahun];
        }

        $tahun = min($tahun, 2025);

        return [
            ...$data,
            'tempat_lahir'        => $this->faker->randomElement(['Medan', 'Tarutung', 'Balige', 'Siborong-borong', 'Pematangsiantar', 'Jakarta', 'Pekanbaru', 'Dolok Sanggul']),
            'tanggal_lahir'       => sprintf('%04d-%02d-%02d', $tahun, mt_rand(1, 12), mt_rand(1, 28)),
            'agama'               => mt_rand(1, 10) <= 8 ? 'Kristen Protestan' : 'Katolik',
            'status_perkawinan'   => $tahun < 2000 ? 'Kawin' : 'Belum Kawin',
            'pendidikan_terakhir' => $this->faker->randomElement(['SMA/SMK', 'D3', 'D4/S1', 'S2', 'SMP']),
            'pekerjaan'           => self::PEKERJAAN[array_rand(self::PEKERJAAN)],
            'golongan_darah'      => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'kewarganegaraan'     => 'WNI',
            'alamat_jalan'        => 'Jl. ' . $this->faker->streetName() . ' No. ' . mt_rand(1, 200),
            'rt'                  => sprintf('%03d', mt_rand(1, 15)),
            'rw'                  => sprintf('%03d', mt_rand(1, 10)),
            'desa_kode'           => $this->desa === [] ? null : $this->desa[array_rand($this->desa)],
            'kode_pos'            => (string) mt_rand(20111, 29999),
            'no_hp'               => '0812' . mt_rand(10000000, 99999999),
        ];
    }

    /**
     * Akun demo (password sama untuk semua: Demo#12345).
     */
    private function buatAkunDemo(int $margaId, ?Person $member): void
    {
        $users   = new UserModel();
        $medan   = $this->db->table('punguan')->where('slug', 'medan')->get()->getRow();
        $medanId = $medan !== null ? (int) $medan->id : null;
        $ayahId  = $member?->induk_id;
        $akun    = [
            ['ketuaadat', 'ketuaadat@silsilah.local', 'ketua_adat', null],
            ['verifikator', 'verifikator@silsilah.local', 'verifikator', null],
            ['penatua', 'penatua@silsilah.local', 'penatua', null],
            ['humas', 'humas@silsilah.local', 'humas', null],
            ['amang', 'amang@silsilah.local', 'member', $ayahId],
            ['member', 'member@silsilah.local', 'member', $member?->id],
            ['calon', 'calon@silsilah.local', 'calon', null],
        ];

        foreach ($akun as [$username, $email, $group, $personId]) {
            if ($users->findByCredentials(['email' => $email]) !== null) {
                continue;
            }
            $users->save(new User([
                'username'   => $username,
                'email'      => $email,
                'password'   => 'Demo#12345',
                'marga_id'   => $margaId,
                'person_id'  => $personId,
                'punguan_id' => $medanId,
            ]));
            $user = $users->findById($users->getInsertID());
            $user->activate();
            $user->syncGroups($group);

            if ($group === 'penatua' && $medanId !== null) {
                (new LingkupAdmin())->simpan($user->id, [['jenis' => 'punguan', 'nilai' => (string) $medanId]]);
            }
            if ($group === 'calon' && $ayahId !== null) {
                // Adik member mendaftarkan keluarganya; validator keluarga: ayahnya (akun "amang").
                $amang = $users->findByCredentials(['email' => 'amang@silsilah.local']);
                (new UsulanService())->ajukanPendaftaran(
                    $user,
                    $this->service->ambil($ayahId),
                    [],
                    [
                        'nama_lengkap'   => 'Calon Pardosi (contoh)',
                        'jenis_kelamin'  => 'L',
                        'tanggal_lahir'  => '1968-03-14',
                        'kabupaten_kode' => '12.71',
                        'no_hp'          => '081200001111',
                    ],
                    'Adik kandung member (contoh).',
                    [
                        'istri' => ['nama_lengkap' => 'Rotua br. Sinaga (contoh)', 'marga_nama' => 'Sinaga', 'tahun_lahir' => 1971],
                        'anak'  => [
                            ['nama_lengkap' => 'Yosua Pardosi (contoh)', 'jenis_kelamin' => 'L', 'tahun_lahir' => 1995],
                            ['nama_lengkap' => 'Grace br. Pardosi (contoh)', 'jenis_kelamin' => 'P', 'tahun_lahir' => 1998],
                        ],
                    ],
                    $medanId,
                    $amang?->id,
                );
            }
        }

        echo '  Akun demo (password Demo#12345): ketuaadat@, verifikator@, penatua@, humas@, amang@, member@, calon@silsilah.local' . PHP_EOL;
    }
}
