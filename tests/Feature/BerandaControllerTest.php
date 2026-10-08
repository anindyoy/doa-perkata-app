<?php

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatDoaBeranda(string $slug, Kategori $kategori, int $urutan, string $judul = 'Judul doa', string $transliterasi = 'transliterasi'): Doa
{
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

it('menampilkan doa berurutan beserta kategori berurutan', function () {
    $kategoriKedua = Kategori::create(['nama' => 'Kedua', 'slug' => 'kedua', 'urutan' => 2]);
    $kategoriPertama = Kategori::create(['nama' => 'Pertama', 'slug' => 'pertama', 'urutan' => 1]);
    $doaKedua = buatDoaBeranda('doa-kedua', $kategoriKedua, 2);
    $doaPertama = buatDoaBeranda('doa-pertama', $kategoriPertama, 1);

    $response = $this->get(route('beranda'));

    $response->assertOk()->assertViewIs('beranda');
    expect($response->viewData('doa')->pluck('id')->all())->toBe([$doaPertama->id, $doaKedua->id]);
    expect($response->viewData('kategori')->pluck('id')->all())->toBe([$kategoriPertama->id, $kategoriKedua->id]);
    expect($response->viewData('cari'))->toBe('');
    expect($response->viewData('slugKategori'))->toBe('');
});

it('menyaring doa berdasarkan teks cari dan kategori', function () {
    $kategoriPilihan = Kategori::create(['nama' => 'Pilihan', 'slug' => 'pilihan', 'urutan' => 1]);
    $kategoriHarian = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 2]);
    $cocok = buatDoaBeranda('doa-cocok', $kategoriPilihan, 1, 'Alhamdulillah', 'alhamdulillah');
    buatDoaBeranda('doa-kategori-lain', $kategoriHarian, 1, 'Alhamdulillah lain', 'lain');
    buatDoaBeranda('doa-teks-lain', $kategoriPilihan, 2, 'Doa berbeda', 'berbeda');

    $response = $this->get(route('beranda', ['cari' => 'alhamdulillah', 'kategori' => 'pilihan']));

    $response->assertOk();
    expect($response->viewData('doa')->pluck('id')->all())->toBe([$cocok->id]);
    expect($response->viewData('cari'))->toBe('alhamdulillah');
    expect($response->viewData('slugKategori'))->toBe('pilihan');
    expect($response->viewData('doa')->url(2))->toContain('cari=alhamdulillah');
});

it('menyembunyikan doa tanpa arti kata', function () {
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
    $doaBerisi = buatDoaBeranda('doa-berisi-kata', $kategori, 2);

    $response = $this->get(route('beranda'));

    $response->assertOk();
    expect($response->viewData('doa')->pluck('id')->all())->toBe([$doaBerisi->id]);
    expect($response->viewData('doa')->pluck('id')->all())->not->toContain($doaKosong->id);
});
