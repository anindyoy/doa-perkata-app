<?php

use App\Models\Doa;
use App\Models\KataDoa;
use App\Models\Kategori;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function buatDoaModel(string $slug): Doa
{
    return Doa::create([
        'judul' => $slug,
        'slug' => $slug,
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);
}

it('menyelesaikan relasi kategori dan kata', function () {
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

    expect($doa->kategori->id)->toBe($kategori->id);
    expect($kata->doa->id)->toBe($doa->id);
    expect($kategori->doa->pluck('id')->all())->toBe([$doa->id]);
    expect($kata->usesTimestamps())->toBeFalse();
});

it('memakai kolom auth kustom dan mengurutkan doa tersimpan', function () {
    $pengguna = Pengguna::create([
        'nama' => 'Pengguna Uji',
        'email' => 'model@example.test',
        'kata_sandi' => 'kata-sandi-rahasia',
    ]);
    $doaLama = buatDoaModel('doa-lama');
    $doaBaru = buatDoaModel('doa-baru');
    $pengguna->doaTersimpan()->attach($doaLama->id, ['disimpan_pada' => '2026-01-01 00:00:00']);
    $pengguna->doaTersimpan()->attach($doaBaru->id, ['disimpan_pada' => '2026-02-01 00:00:00']);

    expect($pengguna->getAuthPasswordName())->toBe('kata_sandi');
    expect($pengguna->getAuthPassword())->toBe($pengguna->kata_sandi);
    expect($pengguna->getRememberTokenName())->toBe('token_ingat');
    expect(Hash::check('kata-sandi-rahasia', $pengguna->kata_sandi))->toBeTrue();
    expect($pengguna->doaTersimpan->pluck('id')->all())->toBe([$doaBaru->id, $doaLama->id]);
    expect($pengguna->toArray())->not->toHaveKey('kata_sandi');
    expect($pengguna->toArray())->not->toHaveKey('token_ingat');
});
