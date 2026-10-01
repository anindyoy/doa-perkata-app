<?php

namespace Tests\Feature;

use App\Models\Doa;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportDuaDhikrTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_valid_records_and_updates_existing_records_without_overwriting_admin_changes(): void
    {
        $path = $this->buatDatasetLokal([
            ['title' => 'Acak', 'arabic' => 'النَّصُّ', 'latin' => 'an-naṣṣu', 'translation' => 'teks'],
            ['title' => 'Acak', 'arabic' => 'نَصٌّ ثَانٍ'],
            ['title' => '', 'arabic' => 'تُتْرَكُ'],
        ]);
        $this->buatDoa('doa-acak');

        $this->artisan('import:dua-dhikr', ['kategori' => 'daily-dua', '--path' => $path])
            ->expectsOutputToContain('Selesai: 2 doa baru, 0 diperbarui')
            ->assertSuccessful();

        $doaPertama = Doa::where('id_eksternal', 'daily-dua:acak')->firstOrFail();
        $doaKedua = Doa::where('id_eksternal', 'daily-dua:acak-2')->firstOrFail();
        $kategoriAdmin = Kategori::create(['nama' => 'Pilihan Admin', 'slug' => 'pilihan-admin', 'urutan' => 9]);
        $doaPertama->update(['kategori_id' => $kategoriAdmin->id, 'urutan' => 9]);
        File::put($this->datasetBerkas($path), json_encode([
            ['title' => 'Acak', 'arabic' => 'النَّصُّ الْجَدِيدُ', 'translation' => 'teks baru'],
            ['title' => 'Acak', 'arabic' => 'نَصٌّ ثَانٍ مُحَدَّثٌ'],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('import:dua-dhikr', ['kategori' => 'daily-dua', '--path' => $path])
            ->expectsOutputToContain('Selesai: 0 doa baru, 2 diperbarui')
            ->assertSuccessful();

        $this->assertSame('Acak', $doaPertama->fresh()->judul);
        $this->assertSame('النَّصُّ الْجَدِيدُ', $doaPertama->fresh()->teks_arab);
        $this->assertSame($kategoriAdmin->id, $doaPertama->fresh()->kategori_id);
        $this->assertSame(9, $doaPertama->fresh()->urutan);
        $this->assertSame('doa-acak-daily-dua', $doaPertama->slug);
        $this->assertSame('acak-2', $doaKedua->slug);
        $this->assertDatabaseMissing('doa', ['id_eksternal' => 'daily-dua:']);

        File::deleteDirectory($path);
    }

    public function test_it_fails_when_the_local_dataset_file_does_not_exist(): void
    {
        $path = sys_get_temp_dir().'/dua-dhikr-missing-'.uniqid();

        $this->artisan('import:dua-dhikr', ['--path' => $path])
            ->expectsOutputToContain('Berkas tidak ditemukan:')
            ->assertFailed();
    }

    public function test_it_fails_when_the_dataset_is_empty_or_invalid(): void
    {
        $path = $this->buatDatasetLokal([]);

        $this->artisan('import:dua-dhikr', ['--path' => $path])
            ->expectsOutput('Data kosong atau format JSON tidak sesuai.')
            ->assertFailed();

        File::put($this->datasetBerkas($path), '{invalid');

        $this->artisan('import:dua-dhikr', ['--path' => $path])
            ->expectsOutput('Data kosong atau format JSON tidak sesuai.')
            ->assertFailed();

        File::deleteDirectory($path);
    }

    public function test_it_downloads_the_default_dataset_and_generates_word_translations_when_requested(): void
    {
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
    }

    private function buatDatasetLokal(array $data): string
    {
        $path = sys_get_temp_dir().'/dua-dhikr-'.uniqid();
        File::ensureDirectoryExists(dirname($this->datasetBerkas($path)));
        File::put($this->datasetBerkas($path), json_encode($data, JSON_THROW_ON_ERROR));

        return $path;
    }

    private function datasetBerkas(string $path): string
    {
        return rtrim($path, '/').'/data/dua-dhikr/daily-dua/id.json';
    }

    private function buatDoa(string $slug): Doa
    {
        return Doa::create([
            'judul' => 'Doa bentrok slug',
            'slug' => $slug,
            'teks_arab' => 'نَصٌّ',
            'terjemahan' => 'Teks',
        ]);
    }
}
