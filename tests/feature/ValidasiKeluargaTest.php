<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\MargaSeeder;
use App\Database\Seeds\PartuturanSeeder;
use App\Database\Seeds\PunguanSeeder;
use App\Entities\Person;
use App\Models\UserModel;
use App\Services\LingkupAdmin;
use App\Services\PersonService;
use App\Services\UsulanService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Validasi dua lapis (keluarga garis langsung → penatua punguan), punguan,
 * serta halaman Jalur saya, Keluarga dekat, dan Kenali Marga.
 *
 * Pohon: G1 → G2 → Ompung (G3) → Ayah (G4) → Abang (G5) → Anak Abang (G6)
 *
 * @internal
 */
final class ValidasiKeluargaTest extends CIUnitTestCase
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
    private int $punguanLain;

    /**
     * @var array<string, Person>
     */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PartuturanSeeder::class);
        $this->seed(PunguanSeeder::class);
        $this->db->table('wilayah')->insertBatch([
            ['kode' => '12', 'nama' => 'Sumatera Utara', 'tingkat' => 1, 'induk_kode' => null],
            ['kode' => '12.71', 'nama' => 'Kota Medan', 'tingkat' => 2, 'induk_kode' => '12'],
        ]);

        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 2, 'pra_marga' => json_encode([['nama' => 'Leluhur Pra', 'keterangan' => '']])]);
        $this->medan = (int) $this->db->table('punguan')->where('slug', 'medan')->get()->getRow()->id;
        $this->db->table('punguan')->insert(['marga_id' => $this->margaId, 'nama' => 'Punguan Jakarta', 'slug' => 'jakarta', 'tingkat' => 'daerah', 'is_active' => 1]);
        $this->punguanLain = (int) $this->db->insertID();

        $s = new PersonService();
        $this->p['G1']     = $s->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Op. Dongan'], null);
        $this->p['G2']     = $s->tambahAnak($this->p['G1']->id, ['nama_lengkap' => 'Op. Ledung', 'jenis_kelamin' => 'L'], null);
        $this->p['Ompung'] = $s->tambahAnak($this->p['G2']->id, ['nama_lengkap' => 'Ompung Contoh', 'jenis_kelamin' => 'L'], null);
        $this->p['Ayah']   = $s->tambahAnak($this->p['Ompung']->id, ['nama_lengkap' => 'Ayah Contoh', 'jenis_kelamin' => 'L'], null);
        $this->p['Abang']  = $s->tambahAnak($this->p['Ayah']->id, ['nama_lengkap' => 'Abang Contoh', 'jenis_kelamin' => 'L'], null);
        $this->p['Cucu']   = $s->tambahAnak($this->p['Abang']->id, ['nama_lengkap' => 'Anak Abang', 'jenis_kelamin' => 'L'], null);
    }

    public function testKandidatValidatorHanyaGarisLangsungDuaSundut(): void
    {
        $ayah   = $this->akun('member', $this->p['Ayah']->id);
        $ompung = $this->akun('member', $this->p['Ompung']->id);
        $this->akun('member', $this->p['G2']->id); // 3 sundut di atas pendaftar baru: bukan kandidat
        $cucu = $this->akun('member', $this->p['Cucu']->id);

        $service = new UsulanService();

        // Pendaftar baru anak Ayah (Sundut 5): Ayah (1 atas) dan Ompung (2 atas).
        $ids = array_column($service->kandidatValidator('daftar_anggota', $this->p['Ayah']), 'user_id');
        sort($ids);
        $this->assertSame([$ayah->id, $ompung->id], $ids);

        // Klaim atas Abang: ke atas Ayah, Ompung; ke bawah anaknya.
        $ids = array_column($service->kandidatValidator('klaim_profil', $this->p['Abang']), 'user_id');
        sort($ids);
        $this->assertSame([$ayah->id, $ompung->id, $cucu->id], $ids);
    }

    public function testDuaLapisDariKepalaKeluargaSampaiMember(): void
    {
        $ayah  = $this->akun('member', $this->p['Ayah']->id);
        $calon = $this->akun('calon');

        // Validator wajib dipilih bila ada kandidat.
        $this->kirim($calon, 'pendaftaran', $this->formDaftar())->assertSessionHas('kesalahan');

        $this->kirim($calon, 'pendaftaran', [...$this->formDaftar(), 'validator_user_id' => $ayah->id])->assertRedirectTo(site_url('pendaftaran'));
        $u = $this->db->table('change_requests')->where('jenis', 'daftar_anggota')->get()->getRowArray();
        $this->assertSame('menunggu', $u['status_keluarga']);
        $this->assertSame($this->medan, (int) $u['punguan_id']);

        // Penatua belum bisa mengesahkan sebelum keluarga memvalidasi.
        $penatua = $this->penatua($this->medan);
        $this->kirim($penatua, 'admin/usulan/' . $u['id'] . '/setujui', [])->assertSessionHas('galat');

        // Penatua punguan lain tidak melihat dan tidak berhak.
        $lain = $this->penatua($this->punguanLain);
        $this->actingAs($lain)->get('admin/usulan')->assertDontSee('Adik Contoh');

        // Lapis 1: ayah membenarkan.
        $this->actingAs($ayah)->get('konfirmasi-keluarga')->assertSee('Adik Contoh');
        $this->kirim($ayah, 'konfirmasi-keluarga/' . $u['id'] . '/validasi', ['benar' => '1']);
        $this->seeInDatabase('change_requests', ['id' => $u['id'], 'status_keluarga' => 'benar']);

        // Lapis 2: penatua Medan mengesahkan.
        $this->kirim($lain, 'admin/usulan/' . $u['id'] . '/setujui', [])->assertSessionHas('galat');
        $this->kirim($penatua, 'admin/usulan/' . $u['id'] . '/setujui', [])->assertRedirectTo(site_url('admin/usulan'));

        $diri = $this->db->table('persons')->where('nama_lengkap', 'Adik Contoh')->get()->getRow();
        $this->assertSame((string) $this->p['Ayah']->id, (string) $diri->induk_id);
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Istri Contoh', 'garis' => 'pasangan', 'marga_nama' => 'Sinaga']);
        $istri = $this->db->table('persons')->where('nama_lengkap', 'Istri Contoh')->get()->getRow();
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Anak Satu', 'induk_id' => $diri->id, 'ibu_id' => $istri->id, 'generasi_ke' => 6]);
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Boru Dua', 'garis' => 'boru']);

        $akun = (new UserModel())->findById($calon->id);
        $this->assertSame((int) $diri->id, (int) $akun->person_id);
        $this->assertSame($this->medan, (int) $akun->punguan_id);
        $this->assertTrue($akun->inGroup('member'));
    }

    public function testKeluargaMenyatakanSalahMemblokirPengesahan(): void
    {
        $ayah  = $this->akun('member', $this->p['Ayah']->id);
        $calon = $this->akun('calon');
        $this->kirim($calon, 'pendaftaran', [...$this->formDaftar(), 'validator_user_id' => $ayah->id]);
        $id = (int) $this->db->table('change_requests')->get()->getRow()->id;

        $this->kirim($ayah, "konfirmasi-keluarga/{$id}/validasi", ['benar' => '0'])->assertSessionHas('galat'); // alasan wajib
        $this->kirim($ayah, "konfirmasi-keluarga/{$id}/validasi", ['benar' => '0', 'catatan' => 'Bukan anak saya']);
        $this->kirim($this->penatua($this->medan), "admin/usulan/{$id}/setujui", [])->assertSessionHas('galat');
        $this->seeInDatabase('change_requests', ['id' => $id, 'status' => 'pending', 'status_keluarga' => 'salah']);
    }

    public function testPenatuaMelewatiValidatorYangTidakAktif(): void
    {
        $ayah  = $this->akun('member', $this->p['Ayah']->id);
        $calon = $this->akun('calon');
        $this->kirim($calon, 'pendaftaran', [...$this->formDaftar(), 'validator_user_id' => $ayah->id]);
        $id      = (int) $this->db->table('change_requests')->get()->getRow()->id;
        $penatua = $this->penatua($this->medan);

        $this->kirim($penatua, "admin/usulan/{$id}/lewati-keluarga", ['alasan' => ''])->assertSessionHas('galat');
        $this->kirim($penatua, "admin/usulan/{$id}/lewati-keluarga", ['alasan' => 'Ayah sudah dikonfirmasi lewat telepon']);
        $this->seeInDatabase('change_requests', ['id' => $id, 'status_keluarga' => 'tidak_ada']);
        $this->kirim($penatua, "admin/usulan/{$id}/setujui", [])->assertRedirectTo(site_url('admin/usulan'));
    }

    public function testMemberMengusulkanAnakSendiriSudahSahKeluarga(): void
    {
        $abang = $this->akun('member', $this->p['Abang']->id);
        $this->kirim($abang, 'anggota/' . $this->p['Abang']->id . '/tambah-anak', ['nama_lengkap' => 'Anak Kedua', 'jenis_kelamin' => 'P']);

        $this->seeInDatabase('change_requests', ['jenis' => 'tambah_anak', 'status_keluarga' => 'benar', 'validator_user_id' => $abang->id]);
    }

    public function testHalamanJalurKeluargaDekatKenaliMargaDanPunguan(): void
    {
        $user = $this->akun('member', $this->p['Abang']->id);

        $garis = $this->actingAs($user)->get('garis');
        $garis->assertSee('sundut ke-5');
        $garis->assertSee('Op. Dongan');
        $garis->assertSee('Sebelum Pardosi');

        $dekat = $this->actingAs($user)->get('keluarga-dekat');
        $dekat->assertSee('Ompung Contoh');
        $dekat->assertSee('Anak Abang');
        $dekat->assertSee('Amang (Bapa)');

        $this->get('kenali-marga')->assertSee('Leluhur Pra');
        $this->get('punguan')->assertSee('Punguan Medan');
        $this->get('punguan/medan')->assertSee('Penatua punguan');

        $this->kirim($this->akun('ketua_adat'), 'admin/sejarah', ['sejarah' => 'Kisah baru', 'pra_nama' => ['A', 'B'], 'pra_ket' => ['', '']]);
        $this->get('kenali-marga')->assertSee('Kisah baru');
    }

    /**
     * @return array<string, mixed>
     */
    private function formDaftar(): array
    {
        return [
            'punguan_id'     => $this->medan,
            'leluhur_id'     => $this->p['Ayah']->id,
            'nama_lengkap'   => 'Adik Contoh',
            'jenis_kelamin'  => 'L',
            'provinsi_kode'  => '12',
            'kabupaten_kode' => '12.71',
            'istri_nama'     => 'Istri Contoh',
            'istri_marga'    => 'Sinaga',
            'anak_nama'      => ['Anak Satu', 'Boru Dua'],
            'anak_jk'        => ['L', 'P'],
            'anak_tahun'     => ['2000', '2003'],
        ];
    }

    private function penatua(int $punguanId): User
    {
        $u = $this->akun('penatua');
        (new LingkupAdmin())->simpan($u->id, [['jenis' => 'punguan', 'nilai' => (string) $punguanId]]);

        return $u;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function kirim(User $user, string $url, array $data): TestResponse
    {
        return $this->actingAs($user)->post($url, [...$data, csrf_token() => csrf_hash()]);
    }

    private function akun(string $group, ?int $personId = null): User
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
            'punguan_id' => $this->medan,
        ]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }
}
