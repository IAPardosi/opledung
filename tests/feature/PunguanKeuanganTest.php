<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\MargaSeeder;
use App\Database\Seeds\PartuturanSeeder;
use App\Database\Seeds\PunguanSeeder;
use App\Entities\Person;
use App\Models\UserModel;
use App\Services\KeuanganService;
use App\Services\LingkupAdmin;
use App\Services\PersonService;
use App\Services\SilsilahQuery;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Mapping keturunan, Data Anggota (status hidup/meninggal),
 * Member Marga vs Member Punguan, dan keuangan punguan.
 *
 * Pohon: G1 → G2 → A (G3) → B1, B2, B3 (G4); B1 → C (G5)
 *
 * @internal
 */
final class PunguanKeuanganTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;
    protected $seed      = MargaSeeder::class;

    private int $margaId;
    private int $medan;
    private int $jakarta;

    /**
     * @var array<string, Person>
     */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PartuturanSeeder::class);
        $this->seed(PunguanSeeder::class);

        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 2]);
        $this->medan = (int) $this->db->table('punguan')->where('slug', 'medan')->get()->getRow()->id;
        $this->db->table('punguan')->insert(['marga_id' => $this->margaId, 'nama' => 'Punguan Jakarta', 'slug' => 'jakarta', 'tingkat' => 'daerah', 'is_active' => 1]);
        $this->jakarta = (int) $this->db->insertID();

        $s = new PersonService();
        $this->p['G1'] = $s->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Op. Dongan'], null);
        $this->p['G2'] = $s->tambahAnak($this->p['G1']->id, ['nama_lengkap' => 'Op. Ledung', 'jenis_kelamin' => 'L'], null);
        $this->p['A']  = $s->tambahAnak($this->p['G2']->id, ['nama_lengkap' => 'Ompu A', 'jenis_kelamin' => 'L'], null);
        $this->p['B1'] = $s->tambahAnak($this->p['A']->id, ['nama_lengkap' => 'Bapa Satu', 'jenis_kelamin' => 'L'], null);
        $this->p['B2'] = $s->tambahAnak($this->p['A']->id, ['nama_lengkap' => 'Bapa Dua', 'jenis_kelamin' => 'L'], null);
        $this->p['B3'] = $s->tambahAnak($this->p['A']->id, ['nama_lengkap' => 'Boru Tiga', 'jenis_kelamin' => 'P'], null);
        $this->p['C']  = $s->tambahAnak($this->p['B1']->id, ['nama_lengkap' => 'Anak Satu', 'jenis_kelamin' => 'L'], null);
    }

    // ---------------------------------------------------------------- Mapping

    public function testMappingFokusHanyaGarisLurusDenganSaudaraDilipat(): void
    {
        $peta = (new SilsilahQuery())->mapping($this->p['C']->id, 'fokus');

        $this->assertSame($this->p['G1']->id, $peta['akar']['id']);
        $this->assertSame(5, $peta['jumlah']);
        $a = $peta['akar']['anak'][0]['anak'][0];
        $this->assertSame('Ompu A', $a['nama_lengkap']);
        $this->assertCount(1, $a['anak']);
        $this->assertSame(3, $a['jumlah_anak']);
        $this->assertSame(2, $a['tersembunyi'], 'Dua saudara Bapa Satu dilipat (+2).');
        $this->assertTrue($a['di_jalur']);

        $c = $a['anak'][0]['anak'][0];
        $this->assertTrue($c['target']);
        $this->assertArrayNotHasKey('nik_enc', $c, 'Mapping hanya membawa kolom publik.');
    }

    public function testMappingKeluargaDanMulaiDariSundutPilihan(): void
    {
        $q = new SilsilahQuery();

        $keluarga = $q->mapping($this->p['C']->id, 'keluarga');
        $a        = $keluarga['akar']['anak'][0]['anak'][0];
        $this->assertCount(3, $a['anak']);
        $this->assertSame(0, $a['tersembunyi']);
        $this->assertFalse($a['anak'][1]['di_jalur']);

        $lengkap = $q->mapping($this->p['C']->id, 'lengkap', 3);
        $this->assertSame($this->p['A']->id, $lengkap['akar']['id']);
        $this->assertSame(5, $lengkap['jumlah']);

        $this->assertNull($q->mapping($this->p['G1']->id + 999, 'fokus'));
    }

    public function testHalamanMappingPublikDanMengikutiAkun(): void
    {
        $r = $this->get('mapping/' . $this->p['C']->id . '?mode=fokus');
        $r->assertOK();
        $r->assertSee('Mapping keturunan');
        $r->assertSee('Fokus saya');
        $r = $this->get('mapping');
        $r->assertOK();
        $r->assertSee('Petakan garis keturunan');

        $member = $this->akun('member', $this->p['C']->id);
        $r = $this->actingAs($member)->get('mapping?mode=lengkap');
        $r->assertOK();
        $r->assertSee('Jalur Anda');
        $r->assertSee('Semua cabang');
    }

    // ------------------------------------------------------- Member punguan

    public function testMemberAjukanDiriLaluDisahkanPenatuaPunguannya(): void
    {
        $member = $this->akun('member', $this->p['B1']->id);
        $r = $this->actingAs($member)->get('anggota/' . $this->p['B1']->id);
        $r->assertSee('Member Marga');
        $r->assertSee('Ajukan jadi Member Punguan');

        $this->kirim($member, 'profil-saya/punguan', ['punguan_id' => $this->medan])->assertSessionHas('sukses');
        $k = $this->db->table('keanggotaan_punguan')->where('person_id', $this->p['B1']->id)->get()->getRowArray();
        $this->assertSame('menunggu', $k['status']);

        // Belum disahkan: tetap Member Marga, pengajuan ganda ditolak.
        $this->actingAs($member)->get('anggota/' . $this->p['B1']->id)->assertSee('menunggu pengesahan Penatua');
        $this->kirim($member, 'profil-saya/punguan', ['punguan_id' => $this->jakarta])->assertSessionHas('galat');

        // Penatua punguan lain tidak berhak.
        $this->kirim($this->penatua($this->jakarta), 'admin/punguan-anggota/' . $k['id'] . '/sahkan', ['aksi' => 'setuju'])->assertSessionHas('galat');

        $penatua = $this->penatua($this->medan);
        $r = $this->actingAs($penatua)->get('admin/punguan-anggota');
        $r->assertOK();
        $r->assertSee('Bapa Satu');
        $this->kirim($penatua, 'admin/punguan-anggota/' . $k['id'] . '/sahkan', ['aksi' => 'setuju'])->assertSessionHas('sukses');
        $this->seeInDatabase('keanggotaan_punguan', ['id' => $k['id'], 'status' => 'aktif', 'nomor_anggota' => 'MED-0001']);

        $r = $this->actingAs($member)->get('anggota/' . $this->p['B1']->id);
        $r->assertSee('Member Punguan · Punguan Medan');
        $r->assertSee('Keuangan saya');
    }

    public function testHumasMengajukanPenatuaLangsungMengesahkan(): void
    {
        $humas = $this->akun('humas');
        $this->kirim($humas, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['B2']->id])->assertSessionHas('sukses');
        $this->seeInDatabase('keanggotaan_punguan', ['person_id' => $this->p['B2']->id, 'status' => 'menunggu', 'nomor_anggota' => null]);

        // Humas tidak dapat mengesahkan.
        $id = (int) $this->db->table('keanggotaan_punguan')->where('person_id', $this->p['B2']->id)->get()->getRow()->id;
        $this->kirim($humas, 'admin/punguan-anggota/' . $id . '/sahkan', ['aksi' => 'setuju'])->assertSessionHas('galat');

        // Humas tidak boleh mendaftarkan ke punguan lain.
        $this->kirim($humas, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->jakarta, 'person_id' => $this->p['C']->id])->assertSessionHas('galat');

        $penatua = $this->penatua($this->medan);
        $this->kirim($penatua, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['C']->id])->assertSessionHas('sukses');
        $this->seeInDatabase('keanggotaan_punguan', ['person_id' => $this->p['C']->id, 'status' => 'aktif']);

        // Penolakan wajib beralasan.
        $this->kirim($penatua, 'admin/punguan-anggota/' . $id . '/sahkan', ['aksi' => 'tolak'])->assertSessionHas('galat');
        $this->kirim($penatua, 'admin/punguan-anggota/' . $id . '/sahkan', ['aksi' => 'tolak', 'catatan' => 'Domisili di Jakarta'])->assertSessionHas('sukses');
        $this->seeInDatabase('keanggotaan_punguan', ['id' => $id, 'status' => 'ditolak']);

        // Member biasa tidak dapat membuka halaman pengurus.
        $member = $this->akun('member', $this->p['B3']->id);
        $this->actingAs($member)->get('admin/punguan-anggota')->assertRedirect();
    }

    // ------------------------------------------------------------- Keuangan

    public function testKeuanganHanyaUntukMemberPunguanDanDivalidasiPenatua(): void
    {
        $humas   = $this->akun('humas');
        $penatua = $this->penatua($this->medan);
        $service = new KeuanganService();

        $iuran = $service->simpanKategori($humas, $this->medan, ['nama' => 'Iuran Bulanan', 'jenis' => 'bulanan', 'nominal_standar' => '25.000', 'is_active' => 1]);
        $ripe  = $service->simpanKategori($humas, $this->medan, ['nama' => 'Toktok Ripe', 'jenis' => 'sekali', 'is_active' => 1]);
        $this->seeInDatabase('keuangan_kategori', ['id' => $iuran, 'nominal_standar' => 25000]);

        // Pengajuan belum disahkan → masih Member Marga → tidak bisa dicatat.
        $this->kirim($humas, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['B1']->id]);
        $kid = (int) $this->db->table('keanggotaan_punguan')->where('person_id', $this->p['B1']->id)->get()->getRow()->id;
        $form = ['keanggotaan_id' => $kid, 'kategori_id' => $iuran, 'periode' => '2026-01', 'periode_sampai' => '2026-03', 'tanggal' => '2026-03-10', 'nominal' => '25000', 'metode' => 'tunai'];
        $this->kirim($humas, 'admin/keuangan/catat?punguan=' . $this->medan, $form)->assertSessionHas('galat');
        $this->dontSeeInDatabase('keuangan_catatan', ['keanggotaan_id' => $kid]);

        $this->kirim($penatua, 'admin/punguan-anggota/' . $kid . '/sahkan', ['aksi' => 'setuju', 'tanggal_masuk' => '2026-01-01']);

        // Humas mencatat 3 bulan sekaligus → menunggu validasi.
        $this->kirim($humas, 'admin/keuangan/catat?punguan=' . $this->medan, $form)->assertSessionHas('sukses');
        $this->assertSame(3, $this->db->table('keuangan_catatan')->where(['keanggotaan_id' => $kid, 'status' => 'menunggu'])->countAllResults());

        // Periode yang sama tidak boleh dicatat dua kali.
        $this->kirim($humas, 'admin/keuangan/catat?punguan=' . $this->medan, [...$form, 'periode' => '2026-02', 'periode_sampai' => ''])->assertSessionHas('galat');

        // Tanggal di masa depan dan nominal kosong ditolak.
        $this->kirim($humas, 'admin/keuangan/catat?punguan=' . $this->medan, [...$form, 'periode' => '2026-04', 'periode_sampai' => '', 'nominal' => '0', 'tanggal' => '2999-01-01'])->assertSessionHas('kesalahan');

        $ids = array_map('intval', array_column($this->db->table('keuangan_catatan')->select('id')->where('keanggotaan_id', $kid)->orderBy('periode')->get()->getResultArray(), 'id'));

        // Humas tidak bisa memvalidasi; penatua punguan lain tidak berhak.
        $this->kirim($humas, 'admin/keuangan/' . $ids[0] . '/validasi', ['aksi' => 'sah'])->assertSessionHas('galat');
        $this->kirim($this->penatua($this->jakarta), 'admin/keuangan/' . $ids[0] . '/validasi', ['aksi' => 'sah'])->assertSessionHas('galat');

        // Penatua Medan: sahkan dua, tolak satu (wajib alasan).
        $this->kirim($penatua, 'admin/keuangan/validasi-banyak', ['ids' => [$ids[0], $ids[1]]])->assertSessionHas('sukses');
        $this->kirim($penatua, 'admin/keuangan/' . $ids[2] . '/validasi', ['aksi' => 'tolak'])->assertSessionHas('galat');
        $this->kirim($penatua, 'admin/keuangan/' . $ids[2] . '/validasi', ['aksi' => 'tolak', 'alasan' => 'Nominal kurang'])->assertSessionHas('sukses');
        $this->seeInDatabase('keuangan_catatan', ['id' => $ids[0], 'status' => 'sah', 'divalidasi_oleh' => $penatua->id]);
        $this->seeInDatabase('keuangan_catatan', ['id' => $ids[2], 'status' => 'ditolak', 'alasan_tolak' => 'Nominal kurang']);

        // Yang sah tidak dapat dihapus; yang ditolak boleh dicatat ulang.
        $this->kirim($humas, 'admin/keuangan/' . $ids[0] . '/hapus', [])->assertSessionHas('galat');
        $this->kirim($humas, 'admin/keuangan/catat?punguan=' . $this->medan, [...$form, 'periode' => '2026-03', 'periode_sampai' => ''])->assertSessionHas('sukses');

        // Catatan penatua sendiri langsung sah.
        $this->kirim($penatua, 'admin/keuangan/catat?punguan=' . $this->medan, ['keanggotaan_id' => $kid, 'kategori_id' => $ripe, 'tanggal' => '2026-04-10', 'nominal' => '100.000'])->assertSessionHas('sukses');
        $this->seeInDatabase('keuangan_catatan', ['kategori_id' => $ripe, 'status' => 'sah', 'nominal' => 100000, 'periode' => null]);

        // Rekap iuran: Jan & Feb sah, Mar menunggu.
        $rekap = $service->rekapBulanan($this->medan, $iuran, 2026);
        $this->assertCount(1, $rekap);
        $this->assertSame(['sah', 'sah', 'menunggu'], array_slice($rekap[0]['bulan'], 0, 3));
        $this->assertSame(50000, $rekap[0]['total']);

        $r = $this->actingAs($penatua)->get('admin/keuangan');
        $r->assertOK();
        $r->assertSee('Rekap Iuran Bulanan 2026');
        $r->assertSee('Bapa Satu');
        $r = $this->actingAs($humas)->get('admin/keuangan/anggota/' . $kid);
        $r->assertOK();
        $r->assertSee('Toktok Ripe');

        // Member melihat keuangannya sendiri di profil.
        $member = $this->akun('member', $this->p['B1']->id);
        $r = $this->actingAs($member)->get('anggota/' . $this->p['B1']->id);
        $r->assertSee('Keuangan saya');
        $r->assertSee('Rp100.000');
        $this->actingAs($member)->get('admin/keuangan')->assertRedirect();
    }

    public function testHumasPunguanLainTidakBisaMencatatAtauMengaturKategori(): void
    {
        $penatua = $this->penatua($this->medan);
        $service = new KeuanganService();
        $iuran   = $service->simpanKategori($penatua, $this->medan, ['nama' => 'Iuran Bulanan', 'jenis' => 'bulanan', 'is_active' => 1]);
        $this->kirim($penatua, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['B1']->id]);
        $kid = (int) $this->db->table('keanggotaan_punguan')->get()->getRow()->id;

        $humasJakarta = $this->akun('humas', null, $this->jakarta);
        $this->kirim($humasJakarta, 'admin/keuangan/catat?punguan=' . $this->jakarta, [
            'keanggotaan_id' => $kid, 'kategori_id' => $iuran, 'periode' => '2026-01', 'tanggal' => '2026-01-10', 'nominal' => '25000',
        ])->assertSessionHas('galat');
        $this->dontSeeInDatabase('keuangan_catatan', ['keanggotaan_id' => $kid]);

        $this->expectException(\App\Exceptions\AksesDitolakException::class);
        $service->simpanKategori($humasJakarta, $this->medan, ['nama' => 'Liar', 'jenis' => 'sekali']);
    }

    // ---------------------------------------------------------- Data anggota

    public function testAdminMenandaiMeninggalMengakhiriKeanggotaanPunguan(): void
    {
        $penatua = $this->penatua($this->medan);
        $this->kirim($penatua, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['B2']->id]);

        $verifikator = $this->akun('verifikator');
        $r = $this->actingAs($verifikator)->get('admin/anggota?member=punguan');
        $r->assertOK();
        $r->assertSee('Bapa Dua');
        $r->assertDontSee('Bapa Satu');

        $this->kirim($verifikator, 'admin/anggota/' . $this->p['B2']->id . '/status', [
            'status_hidup' => 'meninggal', 'tanggal_wafat' => '2026-08-01', 'tempat_makam' => 'Tugu Pardosi',
        ])->assertSessionHas('sukses');

        $this->seeInDatabase('persons', ['id' => $this->p['B2']->id, 'status_hidup' => 'meninggal', 'tanggal_wafat' => '2026-08-01', 'tempat_makam' => 'Tugu Pardosi']);
        $this->seeInDatabase('keanggotaan_punguan', ['person_id' => $this->p['B2']->id, 'status' => 'nonaktif', 'tanggal_keluar' => '2026-08-01']);
        $this->seeInDatabase('audit_logs', ['tabel' => 'persons', 'record_id' => $this->p['B2']->id, 'aksi' => 'ubah']);

        // Dikembalikan hidup: data wafat dibersihkan.
        $this->kirim($verifikator, 'admin/anggota/' . $this->p['B2']->id . '/status', ['status_hidup' => 'hidup'])->assertSessionHas('sukses');
        $this->seeInDatabase('persons', ['id' => $this->p['B2']->id, 'status_hidup' => 'hidup', 'tanggal_wafat' => null]);

        // Orang yang sudah meninggal tidak dapat didaftarkan menjadi member punguan.
        $this->kirim($verifikator, 'admin/anggota/' . $this->p['B3']->id . '/status', ['status_hidup' => 'meninggal', 'tahun_wafat' => '2020']);
        $this->kirim($penatua, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['B3']->id])->assertSessionHas('galat');
    }

    public function testDataAnggotaDibatasiLingkupPenatuaDanTertutupUntukMember(): void
    {
        $penatua = $this->penatua($this->medan);
        $this->kirim($penatua, 'admin/punguan-anggota/tambah', ['punguan_id' => $this->medan, 'person_id' => $this->p['C']->id]);

        // Penatua Medan: member punguannya dan keluarga ≤2 sundut dari akun Medan (tidak ada akun tertaut di sini).
        $r = $this->actingAs($penatua)->get('admin/anggota');
        $r->assertOK();
        $r->assertSee('Anak Satu');
        $r->assertDontSee('Boru Tiga');

        $member = $this->akun('member', $this->p['B3']->id);
        $this->actingAs($member)->get('admin/anggota')->assertRedirect();
    }

    // --------------------------------------------------------------- Bantuan

    private function penatua(int $punguanId): User
    {
        $u = $this->akun('penatua', null, $punguanId);
        (new LingkupAdmin())->simpan($u->id, [['jenis' => 'punguan', 'nilai' => (string) $punguanId]]);

        return $u;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function kirim(User $user, string $url, array $data): TestResponse
    {
        array_walk_recursive($data, static function (&$v): void {
            $v = (string) $v;
        });

        return $this->actingAs($user)->post($url, [...$data, csrf_token() => csrf_hash()]);
    }

    private function akun(string $group, ?int $personId = null, ?int $punguanId = null): User
    {
        static $n = 0;
        $n++;

        $users = new UserModel();
        $users->save(new User([
            'username'   => "{$group}{$n}",
            'email'      => "{$group}{$n}@contoh.test",
            'password'   => 'Rahasia#12345',
            'marga_id'   => $this->margaId,
            'person_id'  => $personId,
            'punguan_id' => $punguanId ?? $this->medan,
        ]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }
}
