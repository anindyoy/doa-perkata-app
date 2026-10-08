<?php

use App\Models\Doa;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatPenggunaTersimpan(): Pengguna
{
    return Pengguna::create([
        'nama' => 'Pengguna Uji',
        'email' => 'uji@example.test',
        'kata_sandi' => 'kata-sandi-rahasia',
    ]);
}

function buatDoaTersimpanUji(): Doa
{
    return Doa::create([
        'judul' => 'Doa Uji',
        'slug' => 'doa-uji',
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);
}

it('mengarahkan tamu ke login dari daftar tersimpan', function () {
    $this->get(route('tersimpan'))->assertRedirect(route('login'));
});

it('menampilkan doa tersimpan milik pengguna yang masuk', function () {
    $pengguna = buatPenggunaTersimpan();
    $doa = buatDoaTersimpanUji();
    $pengguna->doaTersimpan()->attach($doa->id);

    $response = $this->actingAs($pengguna)->get(route('tersimpan'));

    $response->assertOk()->assertViewIs('doa.tersimpan');
    expect($response->viewData('doa')->getCollection()->pluck('id')->all())->toBe([$doa->id]);
});

it('menyimpan doa tanpa baris pivot ganda', function () {
    $pengguna = buatPenggunaTersimpan();
    $doa = buatDoaTersimpanUji();

    $this->actingAs($pengguna)
        ->from(route('doa.show', $doa))
        ->post(route('doa.simpan', $doa))
        ->assertRedirect(route('doa.show', $doa))
        ->assertSessionHas('info', 'Doa disimpan.');
    $this->post(route('doa.simpan', $doa));

    expect($pengguna->doaTersimpan()->whereKey($doa->id)->count())->toBe(1);
    expect($doa->disimpanOleh()->whereKey($pengguna->id)->exists())->toBeTrue();
});

it('menghapus doa dari daftar tersimpan', function () {
    $pengguna = buatPenggunaTersimpan();
    $doa = buatDoaTersimpanUji();
    $pengguna->doaTersimpan()->attach($doa->id);

    $this->actingAs($pengguna)
        ->delete(route('doa.hapus', $doa))
        ->assertSessionHas('info', 'Doa dihapus dari daftar tersimpan.');

    expect($pengguna->doaTersimpan()->whereKey($doa->id)->count())->toBe(0);
});
