<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\MargaSeeder;
use App\Database\Seeds\PartuturanSeeder;
use App\Entities\Person;
use App\Models\UserModel;
use App\Services\LingkupAdmin;
use App\Services\PersonService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Pendaftaran member + kesaksian keluarga + lingkup Admin Wilayah, partuturan, berita & kegiatan.
 *
 * @internal
 */
final class PendaftaranDanKontenTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;
    protected $seed      = MargaSeeder::class;

    private int $margaId;

    /**
     * @var array<string, Person>
     */
    private array $p = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PartuturanSeeder::class);
        $this->db->table('wilayah')->insertBatch([
            ['kode' => '12', 'nama' => 'Sumatera Utara', 'tingkat' => 1, 'induk_kode' => null],
            ['kode' => '12.02', 'nama' => 'Kabupaten Tapanuli Utara', 'tingkat' => 2, 'induk_kode' => '12'],
            ['kode' => '12.02.01', 'nama' => 'Tarutung', 'tingkat' => 3, 'induk_kode' => '12.02'],
            ['kode' => '31', 'nama' => 'DKI Jakarta', 'tingkat' => 1, 'induk_kode' => null],
            ['kode' => '31.71', 'nama' => 'Kota Jakarta Pusat', 'tingkat' => 2, 'induk_kode' => '31'],
        ]);

        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 2]);

        // G1 → G2 → G3 (Ayah) → G4 (Abang, sudah member)
        $s = new PersonService();
        $this->p['G1']    = $s->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Ompu Contoh'], null);
        $this->p['G2']    = $s->tambahAnak($this->p['G1']->id, ['nama_lengkap' => 'Raja Contoh', 'jenis_kelamin' => 'L'], null);
        $this->p['Ayah']  = $s->tambahAnak($this->p['G2']->id, ['nama_lengkap' => 'Ayah Contoh', 'jenis_kelamin' => 'L'], null);
        $this->p['Abang'] = $s->tambahAnak($this->p['Ayah']->id, ['nama_lengkap' => 'Abang Contoh', 'jenis_kelamin' => 'L'], null);
    }

    public function testCalonMendaftarLaluKerabatBersaksiLaluAdminWilayahMenyetujui(): void
    {
        $calon = $this->akun('calon');

        // Calon belum boleh melihat profil anggota.
        $this->actingAs($calon)->get('anggota/' . $this->p['Abang']->id)->assertRedirectTo(site_url('pendaftaran'));

        $this->kirim($calon, 'pendaftaran', [
            'leluhur_id'     => $this->p['G2']->id,
            'antara_nama'    => ['Ayah Baru Contoh'],
            'antara_tahun'   => ['1960'],
            'antara_hidup'   => ['hidup'],
            'nama_lengkap'   => 'Pendaftar Contoh',
            'jenis_kelamin'  => 'L',
            'no_hp'          => '081234567890',
            'provinsi_kode'  => '12',
            'kabupaten_kode' => '12.02',
            'kecamatan_kode' => '12.02.01',
        ])->assertRedirectTo(site_url('pendaftaran'));

        $usulan = $this->db->table('change_requests')->where('jenis', 'daftar_anggota')->get()->getRowArray();
        $this->assertSame('12.02', $usulan['kabupaten_kode']);
        $this->actingAs($calon)->get('pendaftaran')->assertSee('sedang diproses');

        // Kerabat (abang, sudah member) memberi kesaksian.
        $abang = $this->akun('member', $this->p['Abang']->id);
        $this->actingAs($abang)->get('konfirmasi-keluarga')->assertSee('Pendaftar Contoh');
        $this->kirim($abang, 'konfirmasi-keluarga/' . $usulan['id'], ['benar' => '1', 'catatan' => 'Sepupu saya']);
        $this->seeInDatabase('konfirmasi_keluarga', ['change_request_id' => $usulan['id'], 'benar' => 1]);

        // Admin wilayah di luar domisili pendaftar tidak melihat dan tidak bisa menyetujui.
        $adminJakarta = $this->adminWilayah([['jenis' => 'wilayah', 'nilai' => '31']]);
        $this->actingAs($adminJakarta)->get('admin/usulan')->assertDontSee('Ompu Contoh');
        $this->kirim($adminJakarta, 'admin/usulan/' . $usulan['id'] . '/setujui', [])->assertSessionHas('galat');

        // Admin wilayah Sumatera Utara menyetujui.
        $adminSumut = $this->adminWilayah([['jenis' => 'wilayah', 'nilai' => '12']]);
        $this->actingAs($adminSumut)->get('admin/usulan')->assertSee('Raja Contoh');
        $this->kirim($adminSumut, 'admin/usulan/' . $usulan['id'] . '/setujui', [])->assertRedirectTo(site_url('admin/usulan'));

        $ayahBaru = $this->db->table('persons')->where('nama_lengkap', 'Ayah Baru Contoh')->get()->getRow();
        $diri     = $this->db->table('persons')->where('nama_lengkap', 'Pendaftar Contoh')->get()->getRow();
        $this->assertSame('3', (string) $ayahBaru->generasi_ke);
        $this->assertSame('4', (string) $diri->generasi_ke);
        $this->assertSame((string) $ayahBaru->id, (string) $diri->induk_id);

        $akun = (new UserModel())->findById($calon->id);
        $this->assertSame((int) $diri->id, (int) $akun->person_id);
        $this->assertTrue($akun->inGroup('member'));
        $this->assertFalse($akun->inGroup('calon'));
    }

    public function testPendaftaranKeSilsilahPokokDitolak(): void
    {
        $calon = $this->akun('calon');

        $this->kirim($calon, 'pendaftaran', [
            'leluhur_id'     => $this->p['G1']->id,
            'nama_lengkap'   => 'Terlalu Tinggi',
            'jenis_kelamin'  => 'L',
            'provinsi_kode'  => '12',
            'kabupaten_kode' => '12.02',
        ])->assertSessionHas('galat');

        $this->dontSeeInDatabase('change_requests', ['jenis' => 'daftar_anggota']);
    }

    public function testCalonMengklaimDataYangSudahAda(): void
    {
        $calon = $this->akun('calon');
        $this->kirim($calon, 'pendaftaran/klaim/' . $this->p['Abang']->id, [])->assertRedirectTo(site_url('pendaftaran'));
        $id = (int) $this->db->table('change_requests')->where('jenis', 'klaim_profil')->get()->getRow()->id;

        $this->kirim($this->akun('verifikator'), "admin/usulan/{$id}/setujui", []);

        $akun = (new UserModel())->findById($calon->id);
        $this->assertSame($this->p['Abang']->id, (int) $akun->person_id);
        $this->assertTrue($akun->inGroup('member'));
    }

    public function testAdminWilayahCabangHanyaMengeditPomparannya(): void
    {
        $admin = $this->adminWilayah([['jenis' => 'cabang', 'nilai' => (string) $this->p['Ayah']->id]]);
        $s     = new PersonService();

        $anak = $s->tambahAnak($this->p['Abang']->id, ['nama_lengkap' => 'Dalam Cabang', 'jenis_kelamin' => 'L'], $admin);
        $this->assertSame(5, $anak->generasi_ke);

        $this->expectException(\App\Exceptions\AksesDitolakException::class);
        $lain = $s->tambahAnak($this->p['G2']->id, ['nama_lengkap' => 'Cabang Lain', 'jenis_kelamin' => 'L'], null);
        $s->tambahAnak($lain->id, ['nama_lengkap' => 'Di Luar', 'jenis_kelamin' => 'L'], $admin);
    }

    public function testPartuturanDiProfilDanHalamanCek(): void
    {
        $s    = new PersonService();
        $adik = $s->tambahAnak($this->p['Ayah']->id, ['nama_lengkap' => 'Adik Contoh', 'jenis_kelamin' => 'L'], null);
        $user = $this->akun('member', $adik->id);

        $this->actingAs($user)->get('anggota/' . $this->p['Abang']->id)->assertSee('Haha (Angkang)');
        $this->actingAs($user)->get('hubungan?ke=' . $this->p['Ayah']->id)->assertSee('Amang (Bapa)');
        $this->actingAs($user)->get('api/partuturan/' . $this->p['G2']->id)->assertSee('Ompung Doli');
        $this->get('partuturan')->assertSee('Namboru');
    }

    public function testKetuaAdatMengubahIstilah(): void
    {
        $this->kirim($this->akun('verifikator'), 'admin/partuturan', ['sebutan' => ['tulang' => 'X']])->assertRedirect();
        $this->seeInDatabase('partuturan', ['kunci' => 'tulang', 'sebutan' => 'Tulang']);

        $this->kirim($this->akun('ketua_adat'), 'admin/partuturan', ['sebutan' => ['tulang' => 'Tulang (Paman)'], 'keterangan' => ['tulang' => 'Saudara laki-laki ibu.']]);
        $this->seeInDatabase('partuturan', ['kunci' => 'tulang', 'sebutan' => 'Tulang (Paman)']);
    }

    public function testBeritaDanKegiatan(): void
    {
        $humas = $this->akun('humas');

        $this->kirim($humas, 'admin/berita/tambah', ['judul' => 'Pesta Bona Taon Contoh', 'kategori' => 'pengumuman', 'isi' => "Paragraf **penting**.\n\n<script>x</script>", 'status' => 'terbit']);
        $this->kirim($humas, 'admin/berita/tambah', ['judul' => 'Masih Draf', 'kategori' => 'berita', 'isi' => 'Belum tampil', 'status' => 'draft']);

        $this->get('berita')->assertSee('Pesta Bona Taon Contoh');
        $this->get('berita')->assertDontSee('Masih Draf');
        $baca = $this->get('berita/pesta-bona-taon-contoh');
        $baca->assertSee('<strong>penting</strong>');
        $baca->assertDontSee('<script>x</script>');
        $this->get('/')->assertSee('Pesta Bona Taon Contoh');

        $this->kirim($humas, 'admin/kegiatan/tambah', [
            'judul' => 'Partangiangan Contoh', 'jenis' => 'partangiangan', 'mulai' => date('Y-m-d', strtotime('+7 days')) . 'T19:00',
            'lokasi' => 'Rumah Keluarga', 'kabupaten_kode' => '31.71', 'status' => 'terbit',
        ]);
        $this->get('kegiatan')->assertSee('Partangiangan Contoh');
        $this->get('kegiatan/partangiangan-contoh')->assertSee('Kota Jakarta Pusat');

        // Member biasa tidak boleh mengelola konten.
        $this->actingAs($this->akun('member'))->get('admin/berita')->assertRedirect();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function kirim(User $user, string $url, array $data): TestResponse
    {
        return $this->actingAs($user)->post($url, [...$data, csrf_token() => csrf_hash()]);
    }

    /**
     * @param list<array{jenis: string, nilai: string}> $lingkup
     */
    private function adminWilayah(array $lingkup): User
    {
        $admin = $this->akun('admin_wilayah');
        (new LingkupAdmin())->simpan($admin->id, $lingkup);

        return $admin;
    }

    private function akun(string $group, ?int $personId = null): User
    {
        static $n = 0;
        $n++;

        $users = new UserModel();
        $users->save(new User([
            'username'  => "{$group}{$n}",
            'email'     => "{$group}{$n}@contoh.test",
            'password'  => 'Rahasia#12345',
            'marga_id'  => $this->margaId,
            'person_id' => $personId,
        ]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }
}
