<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_prayers_in_order_and_includes_ordered_categories(): void
    {
        $kategoriKedua = Kategori::create(['nama' => 'Kedua', 'slug' => 'kedua', 'urutan' => 2]);
        $kategoriPertama = Kategori::create(['nama' => 'Pertama', 'slug' => 'pertama', 'urutan' => 1]);
        $doaKedua = $this->buatDoa('doa-kedua', $kategoriKedua, 2);
        $doaPertama = $this->buatDoa('doa-pertama', $kategoriPertama, 1);

        $response = $this->get(route('beranda'));

        $response->assertOk()->assertViewIs('beranda');
        $this->assertSame([$doaPertama->id, $doaKedua->id], $response->viewData('doa')->pluck('id')->all());
        $this->assertSame([$kategoriPertama->id, $kategoriKedua->id], $response->viewData('kategori')->pluck('id')->all());
        $this->assertSame('', $response->viewData('cari'));
        $this->assertSame('', $response->viewData('slugKategori'));
    }

    public function test_it_filters_prayers_by_search_text_and_category(): void
    {
        $kategoriPilihan = Kategori::create(['nama' => 'Pilihan', 'slug' => 'pilihan', 'urutan' => 1]);
        $kategoriHarian = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 2]);
        $cocok = $this->buatDoa('doa-cocok', $kategoriPilihan, 1, 'Alhamdulillah', 'alhamdulillah');
        $this->buatDoa('doa-kategori-lain', $kategoriHarian, 1, 'Alhamdulillah lain', 'lain');
        $this->buatDoa('doa-teks-lain', $kategoriPilihan, 2, 'Doa berbeda', 'berbeda');

        $response = $this->get(route('beranda', ['cari' => 'alhamdulillah', 'kategori' => 'pilihan']));

        $response->assertOk();
        $this->assertSame([$cocok->id], $response->viewData('doa')->pluck('id')->all());
        $this->assertSame('alhamdulillah', $response->viewData('cari'));
        $this->assertSame('pilihan', $response->viewData('slugKategori'));
        $this->assertStringContainsString('cari=alhamdulillah', $response->viewData('doa')->url(2));
    }

    public function test_it_hides_prayers_without_word_meanings(): void
    {
        $kategori = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 1]);
        $doaKosong = Doa::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Doa tanpa kata',
            'slug' => 'doa-tanpa-kata',
            'teks_arab' => 'نَصٌّ',
            'transliterasi' => 'nasshun',
            'terjemahan' => 'Teks',
            'urutan' => 1,
        ]);
        $doaBerisi = $this->buatDoa('doa-berisi-kata', $kategori, 2);

        $response = $this->get(route('beranda'));

        $response->assertOk();
        $this->assertSame([$doaBerisi->id], $response->viewData('doa')->pluck('id')->all());
        $this->assertNotContains($doaKosong->id, $response->viewData('doa')->pluck('id')->all());
    }

    private function buatDoa(
        string $slug,
        Kategori $kategori,
        int $urutan,
        string $judul = 'Judul doa',
        string $transliterasi = 'transliterasi'
    ): Doa {
        $doa = Doa::create([
            'kategori_id' => $kategori->id,
            'judul' => $judul,
            'slug' => $slug,
            'teks_arab' => 'نَصٌّ',
            'transliterasi' => $transliterasi,
            'terjemahan' => 'Teks',
            'urutan' => $urutan,
        ]);

        KataDoa::create([
            'doa_id' => $doa->id,
            'kata_arab' => 'نَصٌّ',
            'arti_kata' => 'teks',
            'urutan' => 1,
            'status' => 'terverifikasi',
        ]);

        return $doa;
    }
}
