<?php

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Pengaturan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatDoaDetail(string $slug, ?string $status): Doa
{
    $doa = Doa::create([
        'judul' => "Judul {$slug}",
        'slug' => $slug,
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);

    if ($status !== null) {
        KataDoa::create([
            'doa_id' => $doa->id,
            'kata_arab' => 'نَصٌّ',
            'arti_kata' => 'teks',
            'urutan' => 1,
            'status' => $status,
        ]);
    }

    return $doa;
}

it('menampilkan detail doa dan menandai kata yang belum terverifikasi', function () {
    $doa = buatDoaDetail('doa-detail', 'diterjemahkan_ai');

    $response = $this->get(route('doa.show', $doa));

    $response->assertOk()->assertViewIs('doa.show');
    expect($response->viewData('tersimpan'))->toBeFalse();
    expect($response->viewData('adaBelumTerverifikasi'))->toBeTrue();
});

it('menandai doa sebagai tersimpan untuk pengguna yang masuk', function () {
    $doa = buatDoaDetail('doa-tersimpan', 'terverifikasi');
    $pengguna = Pengguna::create([
        'nama' => 'Pengguna Uji',
        'email' => 'uji@example.test',
        'kata_sandi' => 'rahasia-panjang',
    ]);
    $pengguna->doaTersimpan()->attach($doa->id);

    $response = $this->actingAs($pengguna)->get(route('doa.show', $doa));

    $response->assertOk();
    expect($response->viewData('tersimpan'))->toBeTrue();
    expect($response->viewData('adaBelumTerverifikasi'))->toBeFalse();
});

it('mengarahkan ke doa terverifikasi yang tersedia dan mencatat riwayat', function () {
    $doaList = [];
    for ($index = 1; $index <= 6; $index++) {
        $doaList[] = buatDoaDetail("doa-acak-{$index}", 'terverifikasi');
    }
    $history = array_map(fn (Doa $doa) => $doa->id, array_slice($doaList, 0, 5));

    $response = $this->withSession(['doa_acak_riwayat' => $history])->get(route('doa.acak'));

    $response->assertRedirect(route('doa.show', $doaList[5]));
    expect(session('doa_acak_riwayat'))->toBe(array_merge(array_slice($history, 1), [$doaList[5]->id]));
});

it('mengabaikan riwayat saat mencakup semua doa terverifikasi', function () {
    $doa = buatDoaDetail('doa-satu-satunya', 'terverifikasi');

    $response = $this->withSession(['doa_acak_riwayat' => [$doa->id]])->get(route('doa.acak'));

    $response->assertRedirect(route('doa.show', $doa));
    expect(session('doa_acak_riwayat'))->toBe([$doa->id, $doa->id]);
});

it('memilih doa yang belum terverifikasi saat pengaturan mengizinkan', function () {
    Pengaturan::where('kunci', 'doa_acak_hanya_terverifikasi')->update(['nilai' => '0']);
    $doa = buatDoaDetail('doa-belum-diverifikasi', null);

    $this->get(route('doa.acak'))->assertRedirect(route('doa.show', $doa));
});

it('kembali ke beranda dengan pesan info saat tidak ada doa yang memenuhi syarat', function () {
    $this->get(route('doa.acak'))
        ->assertRedirect(route('beranda'))
        ->assertSessionHas('info', 'Belum ada doa yang bisa ditampilkan secara acak.');
});
