<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\MargaSeeder;
use App\Entities\Person;
use App\Models\UserModel;
use App\Services\PersonService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Alur halaman: publik vs login, usulan → verifikasi, Silsilah Pokok, import.
 *
 * @internal
 */
final class AlurHalamanTest extends CIUnitTestCase
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
     * @var list<Person> G1 → G3 (batas Silsilah Pokok = 2)
     */
    private array $rantai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 2]);

        $service      = new PersonService();
        $g1           = $service->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Leluhur Contoh'], null);
        $g2           = $service->tambahAnak($g1->id, ['nama_lengkap' => 'Anak Contoh', 'jenis_kelamin' => 'L'], null);
        $g3           = $service->tambahAnak($g2->id, ['nama_lengkap' => 'Cucu Contoh', 'jenis_kelamin' => 'L', 'no_hp' => '081234567890'], null);
        $this->rantai = [$g1, $g2, $g3];
    }

    public function testHalamanPublikTanpaLogin(): void
    {
        foreach (['/', 'silsilah', 'generasi', 'generasi?q=Cucu', 'silsilah/' . $this->rantai[2]->id] as $url) {
            $this->get($url)->assertOK();
        }

        $this->get('generasi?q=Cucu')->assertSee('Cucu Contoh');

        $json = $this->get('api/pohon/' . $this->rantai[0]->id . '?kedalaman=3');
        $json->assertOK();
        $json->assertSee('Cucu Contoh');
        $json->assertDontSee('081234567890');
        $json->assertDontSee('no_hp');
    }

    public function testProfilWajibLogin(): void
    {
        $this->get('anggota/' . $this->rantai[2]->id)->assertRedirectTo(site_url('login'));

        $res = $this->actingAs($this->akun('member'))->get('anggota/' . $this->rantai[2]->id);
        $res->assertOK();
        $res->assertSee('Cucu Contoh');
        $res->assertSee('081234567890');
    }

    public function testKontakTersembunyiHanyaUntukDiriDanAdmin(): void
    {
        $this->db->table('persons')->where('id', $this->rantai[2]->id)->update(['sembunyikan_kontak' => 1]);

        $this->actingAs($this->akun('member'))
            ->get('anggota/' . $this->rantai[2]->id)
            ->assertDontSee('081234567890');

        $this->actingAs($this->akun('member', $this->rantai[2]->id))
            ->get('anggota/' . $this->rantai[2]->id)
            ->assertSee('081234567890');

        $this->actingAs($this->akun('verifikator'))
            ->get('anggota/' . $this->rantai[2]->id)
            ->assertSee('081234567890');
    }

    public function testMemberMengusulkanAnakLaluVerifikatorMenyetujui(): void
    {
        $member = $this->akun('member');
        $g3     = $this->rantai[2];

        $this->kirim($member, "anggota/{$g3->id}/tambah-anak", [
            'nama_lengkap'  => 'Usulan Anak',
            'jenis_kelamin' => 'L',
            'nik'           => '1202010101900009',
        ])->assertRedirectTo(site_url('anggota/' . $g3->id));

        $this->dontSeeInDatabase('persons', ['nama_lengkap' => 'Usulan Anak']);
        $usulan = $this->db->table('change_requests')->where('status', 'pending')->get()->getRowArray();
        $this->assertSame('tambah_anak', $usulan['jenis']);
        $this->assertStringNotContainsString('1202010101900009', $usulan['payload'], 'NIK dalam usulan harus terenkripsi.');

        $verifikator = $this->akun('verifikator');
        $this->actingAs($verifikator)->get('admin/usulan')->assertSee('Cucu Contoh');
        $this->actingAs($verifikator)->get('admin/usulan/' . $usulan['id'])->assertSee('Usulan Anak');
        $this->actingAs($verifikator)->get('admin/usulan/' . $usulan['id'])->assertSee('1202********0009');

        $this->kirim($verifikator, 'admin/usulan/' . $usulan['id'] . '/setujui', [])->assertRedirectTo(site_url('admin/usulan'));

        $this->seeInDatabase('persons', ['nama_lengkap' => 'Usulan Anak', 'generasi_ke' => 4, 'induk_id' => $g3->id]);
        $this->seeInDatabase('change_requests', ['id' => $usulan['id'], 'status' => 'disetujui']);
    }

    public function testUsulanSilsilahPokokHanyaDisetujuiKetuaAdat(): void
    {
        $g1 = $this->rantai[0];

        // Verifikator tidak bisa mengisi Generasi 2 langsung, jadi isiannya menjadi usulan.
        $verifikator = $this->akun('verifikator');
        $this->kirim($verifikator, "anggota/{$g1->id}/tambah-anak", ['nama_lengkap' => 'Anak Pokok', 'jenis_kelamin' => 'L']);
        $this->dontSeeInDatabase('persons', ['nama_lengkap' => 'Anak Pokok']);
        $id = (int) $this->db->table('change_requests')->where('status', 'pending')->get()->getRow()->id;

        $this->kirim($verifikator, "admin/usulan/{$id}/setujui", [])->assertSessionHas('galat');
        $this->seeInDatabase('change_requests', ['id' => $id, 'status' => 'pending']);

        $this->kirim($this->akun('ketua_adat'), "admin/usulan/{$id}/setujui", []);
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Anak Pokok', 'generasi_ke' => 2]);
    }

    public function testKlaimProfilMenautkanAkun(): void
    {
        $member = $this->akun('member');
        $g3     = $this->rantai[2];

        $this->kirim($member, "anggota/{$g3->id}/klaim", [])->assertSessionHas('sukses');
        $id = (int) $this->db->table('change_requests')->where('jenis', 'klaim_profil')->get()->getRow()->id;

        $this->kirim($this->akun('verifikator'), "admin/usulan/{$id}/setujui", []);
        $this->seeInDatabase('users', ['id' => $member->id, 'person_id' => $g3->id]);
    }

    public function testMemberTidakBisaMasukAdmin(): void
    {
        $this->actingAs($this->akun('member'))->get('admin/usulan')->assertRedirect();
        $this->actingAs($this->akun('verifikator'))->get('admin/pengguna')->assertRedirect();
        $this->actingAs($this->akun('superadmin'))->get('admin/pengguna')->assertOK();
    }

    public function testImportPratinjauLaluSimpan(): void
    {
        $csv = WRITEPATH . 'uploads/uji-import.csv';
        file_put_contents($csv, implode("\n", [
            'kode_ref,kode_induk,nama_lengkap,jenis_kelamin,nama_pasangan,marga_pasangan',
            'B1,' . $this->rantai[2]->kode_anggota . ',Import Satu,L,Istri Import,Sitompul',
            'B2,B1,Import Dua,P,,',
        ]));

        $service = new \App\Services\ImportService();
        $rows    = $service->bacaFile($csv);

        $pratinjau = $service->proses($this->margaId, $rows, null, false);
        $this->assertTrue($pratinjau['berhasil']);
        $this->dontSeeInDatabase('persons', ['nama_lengkap' => 'Import Satu']);

        $simpan = $service->proses($this->margaId, $rows, null, true);
        $this->assertTrue($simpan['disimpan']);
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Import Satu', 'generasi_ke' => 4, 'garis' => 'utama']);
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Import Dua', 'generasi_ke' => 5, 'garis' => 'boru']);

        $istri = $this->db->table('persons')->where('nama_lengkap', 'Istri Import')->get()->getRow();
        $this->seeInDatabase('persons', ['nama_lengkap' => 'Import Dua', 'ibu_id' => $istri->id]);

        unlink($csv);
    }

    public function testImportDibatalkanBilaAdaBarisGagal(): void
    {
        $csv = WRITEPATH . 'uploads/uji-import-gagal.csv';
        file_put_contents($csv, implode("\n", [
            'kode_ref,kode_induk,nama_lengkap,jenis_kelamin',
            'C1,' . $this->rantai[2]->kode_anggota . ',Harus Batal,L',
            'C2,TIDAK-ADA,Induk Salah,L',
        ]));

        $service = new \App\Services\ImportService();
        $hasil   = $service->proses($this->margaId, $service->bacaFile($csv), null, true);

        $this->assertFalse($hasil['disimpan']);
        $this->assertSame(1, $hasil['jumlah_gagal']);
        $this->dontSeeInDatabase('persons', ['nama_lengkap' => 'Harus Batal']);

        unlink($csv);
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
