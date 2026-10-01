<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\Pengaturan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateKataDoaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_database_api_settings_to_generate_word_translations(): void
    {
        Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => 'https://translator.test/v1/chat/completions']);
        Pengaturan::where('kunci', 'terjemahan_perkata_api_token')->update(['nilai' => 'db-api-token']);
        Pengaturan::where('kunci', 'terjemahan_perkata_model')->update(['nilai' => 'translator-model']);
        Pengaturan::where('kunci', 'terjemahan_perkata_max_tokens')->update(['nilai' => '1234']);
        $tokenSetting = Pengaturan::where('kunci', 'terjemahan_perkata_api_token')->firstOrFail();
        $this->assertSame('********', $tokenSetting->nilai_tampil);

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
    }
}
