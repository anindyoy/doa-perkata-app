<?php

use App\MoonShine\Layouts\AdminLayout;
use App\MoonShine\Pages\PengaturanHalaman;
use App\MoonShine\Resources\DoaResource;
use App\MoonShine\Resources\KataDoaResource;
use App\MoonShine\Resources\KategoriResource;
use App\MoonShine\Resources\PenggunaResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Models\MoonshineUser;

uses(RefreshDatabase::class);

it('mendaftarkan semua resource aplikasi ke moonshine', function () {
    $core = app()->make(CoreContract::class);
    $resources = $core
        ->getResources()
        ->map(fn ($resource): string => $resource::class)
        ->all();

    expect($resources)->toBe([
        DoaResource::class,
        KataDoaResource::class,
        KategoriResource::class,
        PenggunaResource::class,
    ]);

    $pages = $core
        ->getPages()
        ->map(fn ($page): string => $page::class)
        ->all();

    expect($pages)->toContain(PengaturanHalaman::class);

    expect($core->getConfig()->getLayout())->toBe(AdminLayout::class);
});

it('menampilkan semua resource di menu untuk admin yang masuk', function () {
    MoonshineUser::query()->create([
        'email' => 'admin@test.test',
        'password' => Hash::make('password'),
        'name' => 'Admin',
    ]);
    $this->actingAs(MoonshineUser::query()->firstOrFail(), 'moonshine');

    $response = $this->get(route('moonshine.index'));

    $response->assertOk()
        ->assertSee('Doa')
        ->assertSee('Kata Doa (Review)')
        ->assertSee('Kategori')
        ->assertSee('Pengaturan')
        ->assertSee('Pengguna');
});

it('membuka halaman pengaturan sebagai satu form input', function () {
    MoonshineUser::query()->create([
        'email' => 'admin@test.test',
        'password' => Hash::make('password'),
        'name' => 'Admin',
    ]);
    $this->actingAs(MoonshineUser::query()->firstOrFail(), 'moonshine');

    $response = $this->get(route('moonshine.page', ['pageUri' => 'pengaturan-halaman']));

    $response->assertOk()
        ->assertSee('Simpan Pengaturan', false)
        ->assertSee('doa_acak_hanya_terverifikasi', false)
        ->assertSee('terjemahan_perkata_api_url', false)
        ->assertSee('terjemahan_perkata_api_token', false)
        ->assertDontSee('<table', false);
});

it('menyimpan pengaturan lewat method halaman form', function () {
    MoonshineUser::query()->create([
        'email' => 'admin@test.test',
        'password' => Hash::make('password'),
        'name' => 'Admin',
    ]);
    $this->actingAs(MoonshineUser::query()->firstOrFail(), 'moonshine');

    $response = $this->post(route('moonshine.method', ['pageUri' => 'pengaturan-halaman']), [
        'method' => 'simpan',
        'doa_acak_hanya_terverifikasi' => '0',
        'terjemahan_perkata_api_url' => 'https://contoh.test/v1',
        'terjemahan_perkata_api_token' => 'token-baru',
        'terjemahan_perkata_model' => 'model-baru',
        'terjemahan_perkata_max_tokens' => '1234',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('pengaturan', ['kunci' => 'doa_acak_hanya_terverifikasi', 'nilai' => '0']);
    $this->assertDatabaseHas('pengaturan', ['kunci' => 'terjemahan_perkata_model', 'nilai' => 'model-baru']);
});
