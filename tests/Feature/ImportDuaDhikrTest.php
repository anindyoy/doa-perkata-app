<?php

use App\Models\Doa;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function berkasDatasetImpor(string $path): string
{
    return rtrim($path, '/').'/data/dua-dhikr/daily-dua/id.json';
}

function buatDatasetLokalImpor(array $data): string
{
    $path = sys_get_temp_dir().'/dua-dhikr-'.uniqid();
    File::ensureDirectoryExists(dirname(berkasDatasetImpor($path)));
    File::put(berkasDatasetImpor($path), json_encode($data, JSON_THROW_ON_ERROR));

    return $path;
}

function buatDoaBentrokSlug(string $slug): Doa
{
    return Doa::create([
        'judul' => 'Doa bentrok slug',
        'slug' => $slug,
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);
}

it('mengimpor data valid dan memperbarui tanpa menimpa perubahan admin', function () {
    $path = buatDatasetLokalImpor([
        ['title' => 'Acak', 'arabic' => 'النَّصُّ', 'latin' => 'an-naṣṣu', 'translation' => 'teks'],
        ['title' => 'Acak', 'arabic' => 'نَصٌّ ثَانٍ'],
        ['title' => '', 'arabic' => 'تُتْرَكُ'],
    ]);
    buatDoaBentrokSlug('doa-acak');

    $this->artisan('import:dua-dhikr', ['kategori' => 'daily-dua', '--path' => $path])
        ->expectsOutputToContain('Selesai: 2 doa baru, 0 diperbarui')
        ->assertSuccessful();

    $doaPertama = Doa::where('id_eksternal', 'daily-dua:acak')->firstOrFail();
    $doaKedua = Doa::where('id_eksternal', 'daily-dua:acak-2')->firstOrFail();
    $kategoriAdmin = Kategori::create(['nama' => 'Pilihan Admin', 'slug' => 'pilihan-admin', 'urutan' => 9]);
    $doaPertama->update(['kategori_id' => $kategoriAdmin->id, 'urutan' => 9]);
    File::put(berkasDatasetImpor($path), json_encode([
        ['title' => 'Acak', 'arabic' => 'النَّصُّ الْجَدِيدُ', 'translation' => 'teks baru'],
        ['title' => 'Acak', 'arabic' => 'نَصٌّ ثَانٍ مُحَدَّثٌ'],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('import:dua-dhikr', ['kategori' => 'daily-dua', '--path' => $path])
        ->expectsOutputToContain('Selesai: 0 doa baru, 2 diperbarui')
        ->assertSuccessful();

    expect($doaPertama->fresh()->judul)->toBe('Acak');
    expect($doaPertama->fresh()->teks_arab)->toBe('النَّصُّ الْجَدِيدُ');
    expect($doaPertama->fresh()->kategori_id)->toBe($kategoriAdmin->id);
    expect($doaPertama->fresh()->urutan)->toBe(9);
    expect($doaPertama->slug)->toBe('doa-acak-daily-dua');
    expect($doaKedua->slug)->toBe('acak-2');
    $this->assertDatabaseMissing('doa', ['id_eksternal' => 'daily-dua:']);

    File::deleteDirectory($path);
});

it('gagal saat berkas dataset lokal tidak ada', function () {
    $path = sys_get_temp_dir().'/dua-dhikr-missing-'.uniqid();

    $this->artisan('import:dua-dhikr', ['--path' => $path])
        ->expectsOutputToContain('Berkas tidak ditemukan:')
        ->assertFailed();
});

it('gagal saat dataset kosong atau tidak valid', function () {
    $path = buatDatasetLokalImpor([]);

    $this->artisan('import:dua-dhikr', ['--path' => $path])
        ->expectsOutput('Data kosong atau format JSON tidak sesuai.')
        ->assertFailed();

    File::put(berkasDatasetImpor($path), '{invalid');

    $this->artisan('import:dua-dhikr', ['--path' => $path])
        ->expectsOutput('Data kosong atau format JSON tidak sesuai.')
        ->assertFailed();

    File::deleteDirectory($path);
});

it('mengunduh dataset bawaan dan generate arti kata saat diminta', function () {
    Http::preventStrayRequests();
    Http::fake([
        'raw.githubusercontent.com/fitrahive/dua-dhikr/main/data/dua-dhikr/daily-dua/id.json' => Http::response([
            ['title' => 'Doa Online', 'arabic' => 'الْحَمْدُ'],
        ]),
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '[{"kata_arab":"الْحَمْدُ","arti_kata":"pujian"}]'],
            ]],
        ]),
    ]);

    $this->artisan('import:dua-dhikr', ['--generate' => true])
        ->expectsOutputToContain('Mengunduh https://raw.githubusercontent.com/fitrahive/dua-dhikr/main/data/dua-dhikr/daily-dua/id.json')
        ->assertSuccessful();

    $doa = Doa::where('id_eksternal', 'daily-dua:doa-online')->firstOrFail();
    $this->assertDatabaseHas('kata_doa', [
        'doa_id' => $doa->id,
        'kata_arab' => 'الْحَمْدُ',
        'status' => 'diterjemahkan_ai',
    ]);
});
