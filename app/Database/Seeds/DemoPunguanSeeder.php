<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Contoh Member Punguan dan keuangan Punguan Medan (hanya development).
 * Dipanggil dari DemoSilsilahSeeder.
 */
class DemoPunguanSeeder extends Seeder
{
    public function run(): void
    {
        $medan = $this->db->table('punguan')->where('slug', 'medan')->get()->getRowArray();
        if ($medan === null || $this->db->table('keuangan_kategori')->where('punguan_id', $medan['id'])->countAllResults() > 0) {
            return;
        }
        $pid     = (int) $medan['id'];
        $akun    = array_column($this->db->table('users')->select('id, username, person_id')->get()->getResultArray(), null, 'username');
        $humas   = (int) ($akun['humas']['id'] ?? 0) ?: null;
        $penatua = (int) ($akun['penatua']['id'] ?? 0) ?: null;
        $sekarang = date('Y-m-d H:i:s');
        $tahun    = (int) date('Y');
        $bulanIni = (int) date('n');

        // Kategori keuangan yang bisa diatur sendiri oleh punguan.
        $kategori = [];
        foreach ([
            ['Iuran Bulanan', 'bulanan', 25000, 'Iuran rutin setiap bulan untuk kas punguan.', 1],
            ['Hamauliateon', 'sekali', null, 'Ucapan syukur (pesta, kelahiran, kelulusan, dll.).', 2],
            ['Toktok Ripe', 'sekali', 100000, 'Urunan bersama untuk kegiatan besar punguan.', 3],
            ['Sumbangan Duka', 'sekali', 50000, 'Dukungan untuk keluarga yang berduka.', 4],
        ] as [$nama, $jenis, $nominal, $ket, $urut]) {
            $this->db->table('keuangan_kategori')->insert([
                'punguan_id' => $pid, 'nama' => $nama, 'jenis' => $jenis, 'nominal_standar' => $nominal,
                'keterangan' => $ket, 'urutan' => $urut, 'is_active' => 1, 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ]);
            $kategori[$nama] = (int) $this->db->insertID();
        }

        // Member punguan: akun member & amang, plus anggota hidup Sundut 11–13 lainnya.
        $inti   = array_values(array_filter([(int) ($akun['amang']['person_id'] ?? 0), (int) ($akun['member']['person_id'] ?? 0)]));
        $lainnya = array_column($this->db->table('persons')->select('id')
            ->where('garis', 'utama')->where('status_hidup', 'hidup')->whereIn('generasi_ke', [11, 12, 13])
            ->whereNotIn('id', $inti ?: [0])->where('deleted_at', null)
            ->orderBy('id')->limit(12)->get()->getResultArray(), 'id');

        $nomor = 0;
        $anggota = [];
        foreach ([...$inti, ...array_slice($lainnya, 0, 9)] as $i => $personId) {
            $masuk = $i === 0 ? '2024-03-01' : ($i === 1 ? '2025-01-05' : sprintf('%d-%02d-01', $tahun - 1 + intdiv($i, 6), 1 + ($i * 2) % 12));
            $this->db->table('keanggotaan_punguan')->insert([
                'punguan_id' => $pid, 'person_id' => (int) $personId, 'nomor_anggota' => sprintf('MED-%04d', ++$nomor),
                'status' => 'aktif', 'tanggal_masuk' => $masuk, 'diajukan_oleh' => $humas, 'disahkan_oleh' => $penatua,
                'disahkan_at' => $masuk . ' 10:00:00', 'created_at' => $masuk . ' 09:00:00', 'updated_at' => $sekarang,
            ]);
            $anggota[] = ['id' => (int) $this->db->insertID(), 'person_id' => (int) $personId, 'masuk' => $masuk];
        }

        // Satu pengajuan menunggu Penatua dan satu anggota yang pindah (nonaktif).
        if (isset($lainnya[9])) {
            $this->db->table('keanggotaan_punguan')->insert([
                'punguan_id' => $pid, 'person_id' => (int) $lainnya[9], 'status' => 'menunggu', 'diajukan_oleh' => $humas,
                'catatan' => 'Baru pindah ke Medan Johor.', 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ]);
        }
        if (isset($lainnya[10])) {
            $this->db->table('keanggotaan_punguan')->insert([
                'punguan_id' => $pid, 'person_id' => (int) $lainnya[10], 'nomor_anggota' => sprintf('MED-%04d', ++$nomor), 'status' => 'nonaktif',
                'tanggal_masuk' => ($tahun - 2) . '-02-01', 'tanggal_keluar' => ($tahun - 1) . '-12-31', 'diajukan_oleh' => $humas,
                'disahkan_oleh' => $penatua, 'catatan' => 'Pindah domisili ke Jakarta.', 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ]);
        }

        $catat = function (array $a, string $kat, ?string $periode, string $tanggal, int $nominal, string $status, ?string $ket = null, ?string $alasan = null) use ($pid, $kategori, $humas, $penatua): void {
            $this->db->table('keuangan_catatan')->insert([
                'punguan_id' => $pid, 'keanggotaan_id' => $a['id'], 'person_id' => $a['person_id'], 'kategori_id' => $kategori[$kat],
                'periode' => $periode, 'tanggal' => $tanggal, 'nominal' => $nominal, 'metode' => $nominal >= 100000 ? 'transfer' : 'tunai',
                'keterangan' => $ket, 'status' => $status, 'alasan_tolak' => $alasan, 'dicatat_oleh' => $humas,
                'divalidasi_oleh' => $status === 'menunggu' ? null : $penatua, 'divalidasi_at' => $status === 'menunggu' ? null : $tanggal . ' 20:00:00',
                'created_at' => $tanggal . ' 19:00:00', 'updated_at' => $tanggal . ' 19:00:00',
            ]);
        };

        // Iuran bulanan tahun berjalan: sebagian lunas, sebagian menunggak, bulan terakhir menunggu validasi.
        foreach ($anggota as $i => $a) {
            $mulai  = max(1, str_starts_with($a['masuk'], (string) $tahun) ? (int) substr($a['masuk'], 5, 2) : 1);
            $sampai = match (true) {
                $i === 0 => $bulanIni - 2,       // amang: menunggak 2 bulan terakhir
                $i === 1 => $bulanIni - 1,       // member: lunas sampai bulan lalu
                default  => $bulanIni - ($i % 4), // variasi
            };
            for ($m = $mulai; $m <= $sampai; $m++) {
                $catat($a, 'Iuran Bulanan', sprintf('%d-%02d', $tahun, $m), sprintf('%d-%02d-%02d', $tahun, $m, min(28, 5 + $i)), 25000, 'sah');
            }
            if ($i === 1 && $bulanIni >= 1) {
                $catat($a, 'Iuran Bulanan', sprintf('%d-%02d', $tahun, $bulanIni), date('Y-m-d'), 25000, 'menunggu');
            }
            if ($i === 0 && $bulanIni >= 2) {
                $catat($a, 'Iuran Bulanan', sprintf('%d-%02d', $tahun, $bulanIni - 1), date('Y-m-d'), 20000, 'ditolak', null, 'Nominal kurang, iuran Rp25.000.');
            }
        }

        // Hamauliateon & toktok ripe.
        if (isset($anggota[1])) {
            $catat($anggota[1], 'Hamauliateon', null, $tahun . '-06-14', 500000, 'sah', 'Syukuran pernikahan anak');
            $catat($anggota[1], 'Toktok Ripe', null, $tahun . '-04-10', 100000, 'sah', 'Renovasi Tugu Op. Ledung');
        }
        if (isset($anggota[0])) {
            $catat($anggota[0], 'Toktok Ripe', null, $tahun . '-04-12', 100000, 'sah', 'Renovasi Tugu Op. Ledung');
            $catat($anggota[0], 'Hamauliateon', null, date('Y-m-d'), 300000, 'menunggu', 'Syukur kelahiran pahompu');
        }
        foreach (array_slice($anggota, 2, 5) as $a) {
            $catat($a, 'Toktok Ripe', null, $tahun . '-04-15', 100000, 'sah', 'Renovasi Tugu Op. Ledung');
        }
        if (isset($anggota[3])) {
            $catat($anggota[3], 'Sumbangan Duka', null, $tahun . '-08-02', 50000, 'menunggu', 'Duka keluarga Pardosi di Binjai');
        }

        echo '  Punguan Medan: ' . count($anggota) . ' member punguan aktif, 4 kategori keuangan, contoh iuran & validasi.' . PHP_EOL;
    }
}
