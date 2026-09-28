<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\MargaSeeder;
use App\Database\Seeds\PartuturanSeeder;
use App\Entities\Person;
use App\Models\UserModel;
use App\Services\PartuturanService;
use App\Services\PencarianService;
use App\Services\PersonService;
use App\Services\SilsilahQuery;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Kotak keluarga (istri lebih dari satu), huta, saran pencarian, dan Dalihan Na Tolu.
 *
 * Pohon: G1 Op. Dongan → Amani Hotman (G2), beristri Rugun br. Sinaga (1) dan Tiur br. Simanjuntak (2)
 *        ├─ Hotman Pardosi (G3, dari istri 1)
 *        └─ Nurmaida br. Pardosi (G3, boru, dari istri 2) + suami Tambunan → Bere Tambunan (anak boru)
 *
 * @internal
 */
final class KeluargaPencarianTest extends CIUnitTestCase
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
            ['kode' => '12.71', 'nama' => 'Kota Medan', 'tingkat' => 2, 'induk_kode' => '12'],
        ]);
        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 1]);

        $s = new PersonService();
        $this->p['G1']     = $s->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Op. Dongan', 'huta' => 'Lumban Motung'], null);
        $this->p['A']      = $s->tambahAnak($this->p['G1']->id, ['nama_lengkap' => 'Amani Hotman', 'jenis_kelamin' => 'L'], null);
        $this->p['W1']     = $s->tambahPasangan($this->p['A']->id, ['nama_lengkap' => 'Rugun br. Sinaga', 'marga_nama' => 'Sinaga'], [], null);
        $this->p['W2']     = $s->tambahPasangan($this->p['A']->id, ['nama_lengkap' => 'Tiur br. Simanjuntak', 'marga_nama' => 'Simanjuntak'], [], null);
        $this->p['Hotman'] = $s->tambahAnak($this->p['A']->id, ['nama_lengkap' => 'Hotman Pardosi', 'jenis_kelamin' => 'L', 'pasangan_id' => $this->p['W1']->id, 'kabupaten_kode' => '12.71'], null);
        $this->p['Boru']   = $s->tambahAnak($this->p['A']->id, ['nama_lengkap' => 'Nurmaida br. Pardosi', 'jenis_kelamin' => 'P', 'pasangan_id' => $this->p['W2']->id], null);
        $this->p['Suami']  = $s->tambahPasangan($this->p['Boru']->id, ['nama_lengkap' => 'Sahat Tambunan', 'marga_nama' => 'Tambunan'], [], null);
        $this->p['Bere']   = $s->tambahAnak($this->p['Boru']->id, ['nama_lengkap' => 'Bere Tambunan', 'jenis_kelamin' => 'L', 'pasangan_id' => $this->p['Suami']->id], null);
    }

    public function testHutaDiwarisiDariOrangTua(): void
    {
        $this->assertSame('Lumban Motung', $this->p['A']->huta);
        $this->assertSame('Lumban Motung', $this->p['Hotman']->huta);

        $anak = (new PersonService())->tambahAnak($this->p['Hotman']->id, ['nama_lengkap' => 'Anak Rantau', 'jenis_kelamin' => 'L', 'huta' => 'Balige'], null);
        $this->assertSame('Balige', $anak->huta);
    }

    public function testPohonMembawaPasanganDanIbuKe(): void
    {
        $pohon = (new SilsilahQuery())->pohon($this->p['A']->id, 2);

        $this->assertSame(['Rugun br. Sinaga', 'Tiur br. Simanjuntak'], array_column($pohon['pasangan'], 'nama'));
        $this->assertSame([1, 2], array_column($pohon['pasangan'], 'ke'));
        $anak = array_column($pohon['anak'], null, 'nama_lengkap');
        $this->assertSame(1, $anak['Hotman Pardosi']['ibu_ke']);
        $this->assertSame(2, $anak['Nurmaida br. Pardosi']['ibu_ke']);
        $this->assertSame('Sahat Tambunan', $anak['Nurmaida br. Pardosi']['pasangan'][0]['nama']);
        $this->assertArrayNotHasKey('ibu_id', $anak['Hotman Pardosi']);

        $json = json_decode((string) $this->get('api/pohon/' . $this->p['A']->id)->getJSON(), true);
        $this->assertCount(2, $json['pasangan']);
    }

    public function testProfilMenampilkanKotakKeluargaPerIbu(): void
    {
        $member = $this->akun('member', $this->p['Hotman']->id);
        $r      = $this->actingAs($member)->get('anggota/' . $this->p['A']->id);
        $r->assertOK();
        $r->assertSee('Kotak keluarga');
        $r->assertSee('Istri 1');
        $r->assertSee('Istri 2');
        $r->assertSee('Dari Istri 2 · Tiur br. Simanjuntak');
        $r->assertSee('garis-kotak-boru');
    }

    public function testSaranPencarianMengertiNamaAyahKodeTempatDanSalahKetik(): void
    {
        $cari = new PencarianService();

        // Awalan nama didahulukan; gelar/nama lain yang memuat kata itu tetap disarankan.
        $this->assertSame(['Hotman Pardosi', 'Amani Hotman'], array_column($cari->saran($this->margaId, 'hotma'), 'nama_lengkap'));
        $this->assertContains('Hotman Pardosi', array_column($cari->saran($this->margaId, 'hotmen'), 'nama_lengkap'));
        $this->assertEqualsCanonicalizing(['Hotman Pardosi'], array_column($cari->saran($this->margaId, 'anak ni amani'), 'nama_lengkap'));
        $this->assertSame(['Nurmaida br. Pardosi'], array_column($cari->saran($this->margaId, 'boru ni amani'), 'nama_lengkap'));
        $this->assertSame(['Hotman Pardosi'], array_column($cari->saran($this->margaId, 'hotman medan'), 'nama_lengkap'));
        $this->assertSame([$this->p['Hotman']->kode_anggota], array_column($cari->saran($this->margaId, $this->p['Hotman']->kode_anggota), 'kode_anggota'));

        $hasil = $cari->saran($this->margaId, 'hotman pardosi');
        $this->assertSame('anak ni Amani Hotman · pahompu ni Op. Dongan', $hasil[0]['keterangan']);
    }

    public function testTamuTidakMenerimaHutaDanDomisili(): void
    {
        auth()->logout();
        $tamu = json_decode((string) $this->get('api/cari?q=hotman pardosi')->getJSON(), true);
        $this->assertSame('Hotman Pardosi', $tamu[0]['nama_lengkap']);
        $this->assertArrayNotHasKey('huta', $tamu[0]);
        $this->assertArrayNotHasKey('domisili', $tamu[0]);

        $member = $this->akun('member', $this->p['Bere']->id);
        $isi    = json_decode((string) $this->actingAs($member)->get('api/cari?q=hotman pardosi')->getJSON(), true);
        $this->assertSame('Lumban Motung', $isi[0]['huta']);
        $this->assertSame('Medan', $isi[0]['domisili']);
    }

    public function testPosisiDalihanNaTolu(): void
    {
        $s   = new PartuturanService();
        $dnt = fn (string $a, string $b): ?string => $s->dalihan($this->p[$a], $this->p[$b])['kunci'] ?? null;

        $this->assertSame('dongan_tubu', $dnt('Hotman', 'A'));
        $this->assertSame('dongan_tubu', $dnt('Hotman', 'W2'), 'Istri dongan tubu termasuk dongan tubu.');
        $this->assertSame('boru', $dnt('Hotman', 'Boru'));
        $this->assertSame('boru', $dnt('Hotman', 'Suami'));
        $this->assertSame('boru', $dnt('Hotman', 'Bere'));
        $this->assertSame('hula_hula', $dnt('Bere', 'Hotman'), 'Tulang adalah hula-hula bagi bere.');
        $this->assertSame('hula_hula', $dnt('Suami', 'A'));
        $this->assertNull($dnt('Hotman', 'Hotman'));

        $member = $this->akun('member', $this->p['Bere']->id);
        $r      = $this->actingAs($member)->get('anggota/' . $this->p['Hotman']->id);
        $r->assertSee('Dalihan Na Tolu');
        $r->assertSee('Somba marhula-hula');

        $api = json_decode((string) $this->actingAs($member)->get('api/partuturan/' . $this->p['Hotman']->id)->getJSON(), true);
        $this->assertSame('hula_hula', $api['dalihan']['kunci']);
    }

    private function akun(string $group, ?int $personId = null): User
    {
        static $n = 0;
        $n++;

        $users = new UserModel();
        $users->save(new User([
            'username'  => "{$group}k{$n}",
            'email'     => "{$group}k{$n}@contoh.test",
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
