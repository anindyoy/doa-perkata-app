<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Kategori;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DomainModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_category_and_word_relationships(): void
    {
        $kategori = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 1]);
        $doa = Doa::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Doa Harian',
            'slug' => 'doa-harian',
            'teks_arab' => 'الْحَمْدُ',
            'terjemahan' => 'Segala puji',
        ]);
        $kata = KataDoa::create([
            'doa_id' => $doa->id,
            'kata_arab' => 'الْحَمْدُ',
            'arti_kata' => 'segala puji',
            'urutan' => 1,
            'status' => 'terverifikasi',
        ]);

        $this->assertSame($kategori->id, $doa->kategori->id);
        $this->assertSame($doa->id, $kata->doa->id);
        $this->assertSame([$doa->id], $kategori->doa->pluck('id')->all());
        $this->assertFalse($kata->usesTimestamps());
    }

    public function test_it_uses_the_custom_auth_fields_and_sorts_saved_prayers_by_pivot_time(): void
    {
        $pengguna = Pengguna::create([
            'nama' => 'Pengguna Uji',
            'email' => 'model@example.test',
            'kata_sandi' => 'kata-sandi-rahasia',
        ]);
        $doaLama = $this->buatDoa('doa-lama');
        $doaBaru = $this->buatDoa('doa-baru');
        $pengguna->doaTersimpan()->attach($doaLama->id, ['disimpan_pada' => '2026-01-01 00:00:00']);
        $pengguna->doaTersimpan()->attach($doaBaru->id, ['disimpan_pada' => '2026-02-01 00:00:00']);

        $this->assertSame('kata_sandi', $pengguna->getAuthPasswordName());
        $this->assertSame($pengguna->kata_sandi, $pengguna->getAuthPassword());
        $this->assertSame('token_ingat', $pengguna->getRememberTokenName());
        $this->assertTrue(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi));
        $this->assertSame([$doaBaru->id, $doaLama->id], $pengguna->doaTersimpan->pluck('id')->all());
        $this->assertArrayNotHasKey('kata_sandi', $pengguna->toArray());
        $this->assertArrayNotHasKey('token_ingat', $pengguna->toArray());
    }

    private function buatDoa(string $slug): Doa
    {
        return Doa::create([
            'judul' => $slug,
            'slug' => $slug,
            'teks_arab' => 'نَصٌّ',
            'terjemahan' => 'Teks',
        ]);
    }
}
