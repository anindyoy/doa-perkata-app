<?php

use App\MoonShine\Layouts\AdminLayout;
use App\MoonShine\Resources\DoaResource;
use App\MoonShine\Resources\KataDoaResource;
use App\MoonShine\Resources\KategoriResource;
use App\MoonShine\Resources\PengaturanResource;
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
        PengaturanResource::class,
        PenggunaResource::class,
    ]);

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
