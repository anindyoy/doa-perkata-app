<?php

use App\Models\Doa;
use App\MoonShine\Pages\DoaFormHalaman;
use App\MoonShine\Pages\DoaIndexHalaman;
use App\MoonShine\Resources\DoaResource;
use App\Services\GeneratorKataDoa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MoonShine\Core\Core;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\UI\Components\ActionButton;

uses(RefreshDatabase::class);

function buatResourceDoaAdmin(): DoaResource
{
    /** @var MoonShine $core */
    $core = app()->make(MoonShine::class);
    $resource = new DoaResource($core);
    $core->resources([$resource], newCollection: true);
    Core::setInstance($core);

    return $resource;
}

function panggilProtectedAdmin(object $object, string $method): mixed
{
    return (new ReflectionMethod($object, $method))->invoke($object);
}

it('memakai halaman khusus doa pada resource', function () {
    $resource = buatResourceDoaAdmin();
    $pages = panggilProtectedAdmin($resource, 'pages');

    expect(array_map(fn ($p) => is_object($p) ? $p::class : $p, $pages))->toBe(
        ['App\MoonShine\Pages\DoaIndexHalaman', 'App\MoonShine\Pages\DoaFormHalaman', 'App\MoonShine\Pages\DetailHalaman']
    );
});

it('menyediakan tombol generate di index dan form', function () {
    $resource = buatResourceDoaAdmin();

    $tombolIndex = $resource->tombolGenerateIndex();
    $tombolForm = $resource->tombolGenerateForm();

    expect($tombolIndex)->toBeInstanceOf(ActionButton::class);
    expect($tombolForm)->toBeInstanceOf(ActionButton::class);
    expect($tombolIndex->isAsyncMethod())->toBeTrue();
    expect($tombolForm->isAsyncMethod())->toBeTrue();
    expect($tombolIndex->getAsyncMethod())->toBe('generateKata');
    expect($tombolForm->getAsyncMethod())->toBe('generateKata');
});

it('memuat tombol generate di halaman index dan form', function () {
    $resource = buatResourceDoaAdmin();
    /** @var MoonShine $core */
    $core = app()->make(MoonShine::class);

    $index = new DoaIndexHalaman($core);
    $index->setResource($resource);
    $form = new DoaFormHalaman($core);
    $form->setResource($resource);

    $tombolIndex = iterator_to_array($index->getButtons());
    $tombolForm = iterator_to_array($form->getButtons());

    expect($tombolIndex)->not->toBeEmpty();
    expect($tombolForm)->not->toBeEmpty();
    // 4 bawaan (detail/edit/delete/mass-delete) + 1 generate di index.
    expect($tombolIndex)->toHaveCount(5);
    // 2 bawaan (detail/delete) + 1 generate di form.
    expect($tombolForm)->toHaveCount(3);
    expect(collect($tombolIndex)->contains(
        fn ($tombol) => $tombol instanceof ActionButton && $tombol->getAsyncMethod() === 'generateKata'
    ))->toBeTrue();
    expect(collect($tombolForm)->contains(
        fn ($tombol) => $tombol instanceof ActionButton && $tombol->getAsyncMethod() === 'generateKata'
    ))->toBeTrue();
});

it('menandai method generate kata sebagai async', function () {
    $refleksi = new ReflectionMethod(DoaResource::class, 'generateKata');

    expect($refleksi->getAttributes(AsyncMethod::class))->not->toBeEmpty();
});

it('menyimpan draf AI memakai pengaturan saat generate dari admin', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '[{"kata_arab":"الْحَمْدُ","transliterasi_kata":"al-ḥamdu","arti_kata":"segala puji"}]',
                ],
            ]],
        ]),
    ]);

    $doa = Doa::create([
        'judul' => 'Doa admin',
        'slug' => 'doa-admin',
        'teks_arab' => 'الْحَمْدُ',
        'terjemahan' => 'Segala puji',
    ]);

    $jumlah = app()->make(GeneratorKataDoa::class)->generate($doa);

    expect($jumlah)->toBe(1);
    $this->assertDatabaseHas('kata_doa', [
        'doa_id' => $doa->id,
        'kata_arab' => 'الْحَمْدُ',
        'arti_kata' => 'segala puji',
        'status' => 'diterjemahkan_ai',
    ]);
});
