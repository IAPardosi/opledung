<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Database\Seeds\MargaSeeder;
use App\Entities\Person;
use App\Exceptions\AksesDitolakException;
use App\Exceptions\SilsilahException;
use App\Exceptions\ValidasiDataException;
use App\Models\UserModel;
use App\Services\DataPribadiCipher;
use App\Services\PersonService;
use App\Services\SilsilahQuery;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PersonServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;
    protected $seed      = MargaSeeder::class;

    private PersonService $service;
    private int $margaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('wilayah')->insertBatch([
            ['kode' => '12', 'nama' => 'Sumatera Utara', 'tingkat' => 1, 'induk_kode' => null],
            ['kode' => '12.02', 'nama' => 'Kabupaten Tapanuli Utara', 'tingkat' => 2, 'induk_kode' => '12'],
            ['kode' => '12.02.01', 'nama' => 'Tarutung', 'tingkat' => 3, 'induk_kode' => '12.02'],
            ['kode' => '12.02.01.2001', 'nama' => 'Hutatoruan', 'tingkat' => 4, 'induk_kode' => '12.02.01'],
        ]);

        $this->margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        // Batas Silsilah Pokok dikecilkan agar uji hak akses tidak perlu 10 generasi.
        $this->db->table('marga')->where('id', $this->margaId)->update(['batas_silsilah_pokok' => 2]);

        $this->service = new PersonService();
    }

    public function testLeluhurAwalMendapatKodeDanHanyaSatu(): void
    {
        $g1 = $this->leluhur();

        $this->assertSame('PDS-G01-000001', $g1->kode_anggota);
        $this->assertSame(1, $g1->generasi_ke);
        $this->assertSame('utama', $g1->garis);
        $this->seeInDatabase('person_paths', ['ancestor_id' => $g1->id, 'descendant_id' => $g1->id, 'depth' => 0]);

        $this->expectException(SilsilahException::class);
        $this->leluhur();
    }

    public function testAnakLakiMenjadiGarisUtamaDanPerempuanMenjadiBoru(): void
    {
        $g1    = $this->leluhur();
        $laki  = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'Anak Satu', 'jenis_kelamin' => 'L'], null);
        $boru  = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'Anak Dua', 'jenis_kelamin' => 'P'], null);

        $this->assertSame('utama', $laki->garis);
        $this->assertSame('boru', $boru->garis);
        $this->assertSame(2, $laki->generasi_ke);
        $this->assertSame($g1->id, $laki->ayah_id);
        $this->assertSame(1, $laki->urutan_anak);
        $this->assertSame(2, $boru->urutan_anak);
        $this->assertSame('PDS-G02-000002', $laki->kode_anggota);
    }

    public function testKeturunanBoruBerhentiDiAnaknya(): void
    {
        $g1    = $this->leluhur();
        $boru  = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'Boru', 'jenis_kelamin' => 'P'], null);
        $suami = $this->service->tambahPasangan($boru->id, ['nama_lengkap' => 'Suami', 'marga_nama' => 'Sitompul'], [], null);

        $anakBoru = $this->service->tambahAnak($boru->id, [
            'nama_lengkap'  => 'Anak Boru',
            'jenis_kelamin' => 'L',
            'pasangan_id'   => $suami->id,
        ], null);

        $this->assertSame('anak_boru', $anakBoru->garis);
        $this->assertSame($boru->id, $anakBoru->ibu_id);
        $this->assertSame($suami->id, $anakBoru->ayah_id);
        $this->assertSame('Sitompul', $anakBoru->marga_nama);

        $this->expectException(SilsilahException::class);
        $this->expectExceptionMessage('tidak diteruskan');
        $this->service->tambahAnak($anakBoru->id, ['nama_lengkap' => 'Cucu', 'jenis_kelamin' => 'L'], null);
    }

    public function testPasanganDanIbuAnak(): void
    {
        $g1    = $this->leluhur();
        $istri = $this->service->tambahPasangan($g1->id, ['nama_lengkap' => 'Istri', 'marga_nama' => 'Simanjuntak'], ['tanggal_nikah' => '1700-01-01'], null);

        $this->assertSame('pasangan', $istri->garis);
        $this->assertSame('P', $istri->jenis_kelamin);
        $this->seeInDatabase('marriages', ['suami_id' => $g1->id, 'istri_id' => $istri->id, 'urutan' => 1]);
        $this->dontSeeInDatabase('person_paths', ['descendant_id' => $istri->id]);

        $anak = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'Anak', 'jenis_kelamin' => 'L', 'pasangan_id' => $istri->id], null);
        $this->assertSame($istri->id, $anak->ibu_id);

        $this->expectException(ValidasiDataException::class);
        $this->service->tambahPasangan($g1->id, ['nama_lengkap' => 'Tanpa Marga'], [], null);
    }

    public function testOrangTuaHarusPasanganTercatat(): void
    {
        $g1    = $this->leluhur();
        $orang = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'Anak', 'jenis_kelamin' => 'P'], null);

        $this->expectException(SilsilahException::class);
        $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'X', 'jenis_kelamin' => 'L', 'pasangan_id' => $orang->id], null);
    }

    public function testJalurLeluhurDanPohon(): void
    {
        [$g1, $g2, $g3, $g4] = $this->rantai(4);

        $jalur = (new SilsilahQuery())->jalurLeluhur($g4->id);
        $this->assertSame([$g1->id, $g2->id, $g3->id, $g4->id], array_map(static fn (Person $p): int => $p->id, $jalur));

        $pohon = (new SilsilahQuery())->pohon($g1->id, 2);
        $this->assertSame($g2->id, $pohon['anak'][0]['id']);
        $this->assertSame($g3->id, $pohon['anak'][0]['anak'][0]['id']);
        $this->assertSame([], $pohon['anak'][0]['anak'][0]['anak']);
        $this->assertTrue($pohon['anak'][0]['anak'][0]['punya_anak'], 'Node di batas kedalaman harus menandai masih ada anak.');
        $this->assertArrayNotHasKey('no_hp', $pohon);
    }

    public function testSilsilahPokokHanyaKetuaAdat(): void
    {
        $g1          = $this->leluhur();
        $verifikator = $this->akun('verifikator');
        $ketuaAdat   = $this->akun('ketua_adat');

        try {
            $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'G2', 'jenis_kelamin' => 'L'], $verifikator);
            $this->fail('Verifikator tidak boleh mengisi Silsilah Pokok.');
        } catch (AksesDitolakException $e) {
            $this->assertStringContainsString('Silsilah Pokok', $e->getMessage());
        }

        $g2 = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'G2', 'jenis_kelamin' => 'L'], $ketuaAdat);
        // Generasi 3 sudah di luar Silsilah Pokok (batas = 2): verifikator boleh.
        $g3 = $this->service->tambahAnak($g2->id, ['nama_lengkap' => 'G3', 'jenis_kelamin' => 'L'], $verifikator);
        $this->assertSame(3, $g3->generasi_ke);
    }

    public function testMemberDanMargaLainTidakBolehMenambahLangsung(): void
    {
        [, $g2] = $this->rantai(2);

        $this->expectException(AksesDitolakException::class);
        $this->service->tambahAnak($g2->id, ['nama_lengkap' => 'G3', 'jenis_kelamin' => 'L'], $this->akun('member'));
    }

    public function testVerifikatorMargaLainDitolak(): void
    {
        [, $g2] = $this->rantai(2);
        $this->db->table('marga')->insert(['kode' => 'LAIN', 'nama' => 'Marga Lain', 'batas_silsilah_pokok' => 2]);
        $margaLain = (int) $this->db->insertID();

        $this->expectException(AksesDitolakException::class);
        $this->service->tambahAnak($g2->id, ['nama_lengkap' => 'G3', 'jenis_kelamin' => 'L'], $this->akun('verifikator', $margaLain));
    }

    public function testMemberMengubahProfilSendiriTapiTidakYangTerkunci(): void
    {
        [, , $g3] = $this->rantai(3);
        $member   = $this->akun('member', null, $g3->id);

        $hasil = $this->service->ubahProfil($g3->id, ['pekerjaan' => 'Guru', 'generasi_ke' => 99, 'garis' => 'boru'], $member);
        $this->assertSame('Guru', $hasil->pekerjaan);
        $this->assertSame(3, $hasil->generasi_ke, 'Kolom struktur silsilah tidak boleh berubah lewat ubah profil.');
        $this->assertSame('utama', $hasil->garis);

        $this->service->validasi($g3->id, $this->akun('verifikator'));
        $this->assertSame('terverifikasi', $this->service->ambil($g3->id)->status_data);

        $g1 = $this->service->validasi(1, $this->akun('ketua_adat'));
        $this->assertSame('terkunci', $g1->status_data);

        $this->expectException(AksesDitolakException::class);
        $this->service->ubahProfil($g1->id, ['pekerjaan' => 'X'], $this->akun('verifikator'));
    }

    public function testNikTerenkripsiDanTidakBolehGanda(): void
    {
        $g1   = $this->leluhur();
        $anak = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'A', 'jenis_kelamin' => 'L', 'nik' => '1202010101900001'], null);

        $row = $this->db->table('persons')->where('id', $anak->id)->get()->getRowArray();
        $this->assertStringNotContainsString('1202010101900001', (string) $row['nik_enc']);
        $this->assertSame('1202010101900001', (new DataPribadiCipher())->dekripsi($row['nik_enc']));
        $this->dontSeeInDatabase('audit_logs', ['record_id' => $anak->id, 'data_baru LIKE' => '%1202010101900001%']);

        $this->expectException(ValidasiDataException::class);
        $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'B', 'jenis_kelamin' => 'L', 'nik' => '1202010101900001'], null);
    }

    public function testKodeDesaMelengkapiWilayahInduk(): void
    {
        $g1   = $this->leluhur();
        $anak = $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'A', 'jenis_kelamin' => 'L', 'desa_kode' => '12.02.01.2001'], null);

        $this->assertSame('12.02.01', $anak->kecamatan_kode);
        $this->assertSame('12.02', $anak->kabupaten_kode);
        $this->assertSame('12', $anak->provinsi_kode);

        $this->expectException(ValidasiDataException::class);
        $this->service->tambahAnak($g1->id, ['nama_lengkap' => 'B', 'jenis_kelamin' => 'L', 'desa_kode' => '99.99'], null);
    }

    public function testValidasiInputBerbahasaIndonesia(): void
    {
        $g1 = $this->leluhur();

        try {
            $this->service->tambahAnak($g1->id, ['nama_lengkap' => '', 'jenis_kelamin' => 'L', 'no_hp' => '12345'], null);
            $this->fail('Seharusnya gagal validasi.');
        } catch (ValidasiDataException $e) {
            $this->assertArrayHasKey('nama_lengkap', $e->errors());
            $this->assertArrayHasKey('no_hp', $e->errors());
            $this->assertStringContainsString('Nama lengkap', $e->errors()['nama_lengkap']);
        }
    }

    public function testHapusHanyaUntukDataTanpaAnak(): void
    {
        [$g1, $g2] = $this->rantai(2);

        try {
            $this->service->hapus($g1->id, null);
            $this->fail('Data yang masih memiliki anak tidak boleh dihapus.');
        } catch (SilsilahException) {
        }

        $this->service->hapus($g2->id, null);
        $this->seeInDatabase('persons', ['id' => $g2->id, 'deleted_at IS NOT' => null]);
        $this->assertSame(0, (new SilsilahQuery())->jumlahKeturunan($g1->id));
    }

    private function leluhur(): Person
    {
        return $this->service->tambahLeluhurAwal($this->margaId, ['nama_lengkap' => 'Leluhur', 'tahun_lahir' => 1650], null);
    }

    /**
     * Rantai garis utama G1 → Gn.
     *
     * @return list<Person>
     */
    private function rantai(int $jumlah): array
    {
        $hasil = [$this->leluhur()];
        for ($g = 2; $g <= $jumlah; $g++) {
            $hasil[] = $this->service->tambahAnak(end($hasil)->id, ['nama_lengkap' => "G{$g}", 'jenis_kelamin' => 'L'], null);
        }

        return $hasil;
    }

    private function akun(string $group, ?int $margaId = null, ?int $personId = null): User
    {
        static $n = 0;
        $n++;

        $users = new UserModel();
        $users->save(new User([
            'username'  => "{$group}{$n}",
            'email'     => "{$group}{$n}@contoh.test",
            'password'  => 'Rahasia#12345',
            'marga_id'  => $margaId ?? $this->margaId,
            'person_id' => $personId,
        ]));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }
}
