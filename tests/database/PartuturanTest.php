<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Database\Seeds\MargaSeeder;
use App\Entities\Person;
use App\Services\PartuturanService;
use App\Services\PersonService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Pohon uji:
 *
 *   Op (G1)
 *   ├─ A1 (anak ke-1) + W1
 *   │   ├─ A1a (L) + W3
 *   │   │   └─ A1a1 (L)
 *   │   │       └─ A1a1x (L)
 *   │   └─ A1b (P, boru) + H (Sitompul)
 *   │       ├─ AB1 (L, anak boru)
 *   │       └─ AB2 (P, anak boru)
 *   └─ A2 (anak ke-2) + W2
 *       ├─ A2a (L)
 *       └─ A2b (P)
 *
 * @internal
 */
final class PartuturanTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;
    protected $seed      = MargaSeeder::class;

    /**
     * @var array<string, Person>
     */
    private array $p = [];

    private PartuturanService $tutur;

    protected function setUp(): void
    {
        parent::setUp();

        $s       = new PersonService();
        $margaId = (int) $this->db->table('marga')->where('kode', 'PDS')->get()->getRow()->id;
        $anak    = fn (string $induk, string $nama, string $jk, array $extra = []) => $this->p[$nama] = $s->tambahAnak($this->p[$induk]->id, ['nama_lengkap' => $nama, 'jenis_kelamin' => $jk, ...$extra], null);
        $psg     = fn (string $orang, string $nama, string $marga) => $this->p[$nama] = $s->tambahPasangan($this->p[$orang]->id, ['nama_lengkap' => $nama, 'marga_nama' => $marga], [], null);

        $this->p['Op'] = $s->tambahLeluhurAwal($margaId, ['nama_lengkap' => 'Op'], null);
        $anak('Op', 'A1', 'L');
        $anak('Op', 'A2', 'L');
        $psg('A1', 'W1', 'Simanjuntak');
        $psg('A2', 'W2', 'Siregar');
        $anak('A1', 'A1a', 'L');
        $anak('A1', 'A1b', 'P');
        $psg('A1a', 'W3', 'Sinaga');
        $psg('A1b', 'H', 'Sitompul');
        $anak('A1b', 'AB1', 'L', ['pasangan_id' => $this->p['H']->id]);
        $anak('A1b', 'AB2', 'P', ['pasangan_id' => $this->p['H']->id]);
        $anak('A1a', 'A1a1', 'L');
        $anak('A1a1', 'A1a1x', 'L');
        $anak('A2', 'A2a', 'L');
        $anak('A2', 'A2b', 'P');

        $this->tutur = new PartuturanService();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function kasus(): iterable
    {
        // [dari, ke, kunci panggilan]
        yield 'ayah' => ['A1a', 'A1', 'ayah'];
        yield 'ibu (pasangan ayah)' => ['A1a', 'W1', 'ibu'];
        yield 'ompung doli' => ['A1a', 'Op', 'ompung_doli'];
        yield 'ompu' => ['A1a1x', 'Op', 'ompu'];
        yield 'anak' => ['A1', 'A1a', 'anak'];
        yield 'pahompu' => ['Op', 'A1a', 'pahompu'];
        yield 'nini' => ['Op', 'A1a1', 'nini'];
        yield 'ito kandung' => ['A1a', 'A1b', 'ito'];
        yield 'haha dari garis sulung' => ['A2a', 'A1a', 'haha'];
        yield 'anggi dari garis bungsu' => ['A1a', 'A2a', 'anggi'];
        yield 'amangtua' => ['A2a', 'A1', 'amangtua'];
        yield 'amanguda' => ['A1a', 'A2', 'amanguda'];
        yield 'inanguda (istri amanguda)' => ['A1a', 'W2', 'inanguda'];
        yield 'namboru' => ['A1a1', 'A1b', 'namboru'];
        yield 'namboru semarga ayah' => ['A1a1', 'A2b', 'namboru'];
        yield 'ito sepupu semarga' => ['A2a', 'A1b', 'ito'];
        yield 'amangboru (suami namboru)' => ['A1a1', 'H', 'amangboru'];
        yield 'tulang' => ['AB1', 'A1a', 'tulang'];
        yield 'tulang semarga ibu' => ['AB1', 'A2a', 'tulang'];
        yield 'nantulang' => ['AB1', 'W3', 'nantulang'];
        yield 'bere' => ['A1a', 'AB1', 'bere'];
        yield 'anak boru bagi ibunya' => ['A1b', 'AB1', 'anak'];
        yield 'lae (anak tulang)' => ['AB1', 'A1a1', 'lae'];
        yield 'lae (anak namboru)' => ['A1a1', 'AB1', 'lae'];
        yield 'ito (anak namboru bagi laki-laki)' => ['A1a1', 'AB2', 'ito'];
        yield 'ito (anak tulang bagi perempuan)' => ['AB2', 'A1a1', 'ito'];
        yield 'anak dari abang' => ['A1', 'A2a', 'anak'];
        yield 'anak ni ito' => ['A1b', 'A1a1', 'anak_ni_ito'];
        yield 'ompung naposo' => ['AB1', 'A1a1x', 'ompung_naposo'];
        yield 'istri' => ['A1a', 'W3', 'istri'];
        yield 'parumaen' => ['A1', 'W3', 'parumaen'];
        yield 'hela' => ['A1', 'H', 'hela'];
        yield 'simatua doli (dari menantu)' => ['W3', 'A1', 'simatua_doli'];
        yield 'anak (dari ibu yang pasangan)' => ['W1', 'A1a', 'anak'];
        yield 'eda (istri ito bagi perempuan)' => ['A1b', 'W3', 'eda'];
        yield 'lae (suami ito bagi laki-laki)' => ['A1a', 'H', 'lae'];
    }

    /**
     * @dataProvider kasus
     */
    public function testPanggilan(string $dari, string $ke, string $harap): void
    {
        $h = $this->tutur->hubungan($this->p[$dari]->id, $this->p[$ke]->id);

        $this->assertNotNull($h);
        $this->assertSame($harap, $h['kunci'], "{$dari} memanggil {$ke}");
    }

    public function testPanggilanBalikDanJalur(): void
    {
        $h = $this->tutur->hubungan($this->p['AB1']->id, $this->p['A1a']->id);

        $this->assertSame('Tulang', $h['sebutan']);
        $this->assertSame('bere', $h['balik']['kunci']);
        $this->assertSame($this->p['A1']->id, $h['titik_temu']->id);
        $this->assertSame(
            ['AB1', 'A1b', 'A1', 'A1a'],
            array_map(static fn (Person $p): string => $p->nama_lengkap, $h['jalur']),
        );
    }

    public function testIstilahDapatDiubahKetuaAdat(): void
    {
        $this->db->table('partuturan')->insert(['kunci' => 'tulang', 'sebutan' => 'Tulang (disesuaikan)', 'kelompok' => 'paman_bibi']);

        $h = (new PartuturanService())->hubungan($this->p['AB1']->id, $this->p['A1a']->id);
        $this->assertSame('Tulang (disesuaikan)', $h['sebutan']);
    }
}
