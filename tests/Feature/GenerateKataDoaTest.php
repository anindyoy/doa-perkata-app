<?php

use App\Models\Doa;
use App\Models\Pengaturan;
use App\Services\GeneratorKataDoa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('memakai pengaturan API database untuk generate arti kata', function () {
    Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => 'https://translator.test/v1/chat/completions']);
    Pengaturan::where('kunci', 'terjemahan_perkata_api_token')->update(['nilai' => 'db-api-token']);
    Pengaturan::where('kunci', 'terjemahan_perkata_model')->update(['nilai' => 'translator-model']);
    Pengaturan::where('kunci', 'terjemahan_perkata_max_tokens')->update(['nilai' => '1234']);
    $tokenSetting = Pengaturan::where('kunci', 'terjemahan_perkata_api_token')->firstOrFail();
    expect($tokenSetting->nilai_tampil)->toBe('********');

    Http::fake([
        'https://translator.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '[{"kata_arab":"الْحَمْدُ","transliterasi_kata":"al-ḥamdu","arti_kata":"segala puji"}]',
                ],
            ]],
        ]),
    ]);

    $doa = Doa::create([
        'judul' => 'Doa uji',
        'slug' => 'doa-uji',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);

    $this->artisan('generate:kata-doa', ['doa_id' => $doa->id])->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://translator.test/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer db-api-token')
        && $request['model'] === 'translator-model'
        && $request['max_tokens'] === 1234);

    $this->assertDatabaseHas('kata_doa', [
        'doa_id' => $doa->id,
        'kata_arab' => 'الْحَمْدُ',
        'arti_kata' => 'segala puji',
        'status' => 'diterjemahkan_ai',
    ]);
});

it('gagal sebelum request saat pengaturan wajib hilang', function () {
    Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => null]);
    Http::preventStrayRequests();

    $this->artisan('generate:kata-doa', ['doa_id' => 1])
        ->expectsOutput('URL API dan model terjemahan per kata harus diatur di menu Pengaturan.')
        ->assertFailed();
});

it('tidak menyimpan kata saat respons API bukan JSON valid', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => 'bukan JSON'],
            ]],
        ]),
    ]);

    $doa = Doa::create([
        'judul' => 'Doa uji gagal',
        'slug' => 'doa-uji-gagal',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);

    $this->artisan('generate:kata-doa', ['doa_id' => $doa->id])
        ->expectsOutput('  Gagal: Respons AI bukan JSON array yang valid.')
        ->assertSuccessful();

    $this->assertDatabaseMissing('kata_doa', ['doa_id' => $doa->id]);
});

it('meminta target atau opsi semua', function () {
    $this->artisan('generate:kata-doa')
        ->expectsOutput('Berikan {doa_id} atau gunakan --semua.')
        ->assertFailed();
});

it('melapor sukses saat doa yang diminta tidak ada', function () {
    $this->artisan('generate:kata-doa', ['doa_id' => 999])
        ->expectsOutput('Tidak ada doa yang perlu diproses.')
        ->assertSuccessful();
});

it('melewati kata yang ada di mode semua tanpa request', function () {
    Http::preventStrayRequests();
    $doa = Doa::create([
        'judul' => 'Doa sudah diterjemahkan',
        'slug' => 'doa-sudah-diterjemahkan',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);
    $doa->kataDoa()->create([
        'kata_arab' => 'الْحَمْدُ',
        'arti_kata' => 'segala puji',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);

    $this->artisan('generate:kata-doa', ['--semua' => true])
        ->assertSuccessful();

    $this->assertDatabaseHas('kata_doa', [
        'doa_id' => $doa->id,
        'status' => 'terverifikasi',
    ]);
});

it('melewati kata yang ada saat doa spesifik diminta', function () {
    Http::preventStrayRequests();
    $doa = Doa::create([
        'judul' => 'Doa dilewati',
        'slug' => 'doa-dilewati',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);
    $doa->kataDoa()->create([
        'kata_arab' => 'الْحَمْدُ',
        'arti_kata' => 'segala puji',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);

    $this->artisan('generate:kata-doa', ['doa_id' => $doa->id])
        ->expectsOutputToContain("Lewati #{$doa->id} Doa dilewati: sudah punya kata")
        ->assertSuccessful();
});

it('mengganti kata yang ada saat mode ulang aktif', function () {
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '[{"kata_arab":"الْحَمْدُ","arti_kata":"pujian"}]'],
            ]],
        ]),
    ]);
    $doa = Doa::create([
        'judul' => 'Doa dibuat ulang',
        'slug' => 'doa-dibuat-ulang',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);
    $doa->kataDoa()->create([
        'kata_arab' => 'قديم',
        'arti_kata' => 'lama',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);

    $this->artisan('generate:kata-doa', ['doa_id' => $doa->id, '--ulang' => true])
        ->assertSuccessful();

    $this->assertDatabaseMissing('kata_doa', ['doa_id' => $doa->id, 'kata_arab' => 'قديم']);
    $this->assertDatabaseHas('kata_doa', [
        'doa_id' => $doa->id,
        'kata_arab' => 'الْحَمْدُ',
        'status' => 'diterjemahkan_ai',
    ]);
});

it('menyertakan doa yang ada di mode semua saat ulang aktif', function () {
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '[{"kata_arab":"الْحَمْدُ","arti_kata":"pujian"}]'],
            ]],
        ]),
    ]);
    $doa = Doa::create([
        'judul' => 'Doa ulang semua',
        'slug' => 'doa-ulang-semua',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);
    $doa->kataDoa()->create([
        'kata_arab' => 'قديم',
        'arti_kata' => 'lama',
        'urutan' => 1,
        'status' => 'terverifikasi',
    ]);

    $this->artisan('generate:kata-doa', ['--semua' => true, '--ulang' => true])
        ->assertSuccessful();

    $this->assertDatabaseMissing('kata_doa', ['doa_id' => $doa->id, 'kata_arab' => 'قديم']);
    $this->assertDatabaseHas('kata_doa', ['doa_id' => $doa->id, 'arti_kata' => 'pujian']);
});

it('tidak menyimpan item tanpa kolom terjemahan wajib', function () {
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '[{"kata_arab":"الْحَمْدُ"}]'],
            ]],
        ]),
    ]);
    $doa = Doa::create([
        'judul' => 'Doa payload tidak lengkap',
        'slug' => 'doa-payload-tidak-lengkap',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);

    $this->artisan('generate:kata-doa', ['doa_id' => $doa->id])
        ->expectsOutput('  Gagal: Ada item tanpa kata_arab/arti_kata.')
        ->assertSuccessful();

    $this->assertDatabaseMissing('kata_doa', ['doa_id' => $doa->id]);
});

it('melempar exception saat pengaturan tidak lengkap di service', function () {
    Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => null]);

    $doa = Doa::create([
        'judul' => 'Doa test',
        'slug' => 'doa-test',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);

    $generator = app()->make(GeneratorKataDoa::class);

    try {
        $generator->generate($doa);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('URL API dan model terjemahan per kata harus diatur di menu Pengaturan.');
    }
});

it('melempar exception saat teks arab kosong di service', function () {
    Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => 'https://test.com']);
    Pengaturan::where('kunci', 'terjemahan_perkata_model')->update(['nilai' => 'test-model']);

    $doa = Doa::create([
        'judul' => 'Doa test',
        'slug' => 'doa-test',
        'teks_arab' => '',
        'terjemahan' => 'Segala puji',
    ]);

    $generator = app()->make(GeneratorKataDoa::class);

    try {
        $generator->generate($doa);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('Teks Arab doa masih kosong.');
    }
});
