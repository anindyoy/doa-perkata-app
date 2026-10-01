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

    public function test_it_fails_before_sending_a_request_when_required_settings_are_missing(): void
    {
        Pengaturan::where('kunci', 'terjemahan_perkata_api_url')->update(['nilai' => null]);
        Http::preventStrayRequests();

        $this->artisan('generate:kata-doa', ['doa_id' => 1])
            ->expectsOutput('URL API dan model terjemahan per kata harus diatur di menu Pengaturan.')
            ->assertFailed();
    }

    public function test_it_does_not_save_words_when_the_api_response_is_not_valid_json(): void
    {
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
    }

    public function test_it_requires_a_target_or_the_all_option(): void
    {
        $this->artisan('generate:kata-doa')
            ->expectsOutput('Berikan {doa_id} atau gunakan --semua.')
            ->assertFailed();
    }

    public function test_it_reports_success_when_the_requested_prayer_does_not_exist(): void
    {
        $this->artisan('generate:kata-doa', ['doa_id' => 999])
            ->expectsOutput('Tidak ada doa yang perlu diproses.')
            ->assertSuccessful();
    }

    public function test_it_skips_existing_words_in_all_mode_without_sending_a_request(): void
    {
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
    }

    public function test_it_skips_existing_words_when_a_specific_prayer_is_requested(): void
    {
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
    }

    public function test_it_replaces_existing_words_when_repeat_mode_is_enabled(): void
    {
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
    }

    public function test_it_includes_existing_prayers_in_all_mode_when_repeat_is_enabled(): void
    {
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
    }

    public function test_it_does_not_save_an_item_missing_required_translation_fields(): void
    {
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
    }
}
