<?php

use App\Models\Doa;
use App\Models\Kategori;
use App\MoonShine\Pages\DetailHalaman;
use App\MoonShine\Pages\FormHalaman;
use App\MoonShine\Pages\IndexHalaman;
use App\MoonShine\Resources\DoaResource;
use App\MoonShine\Resources\KataDoaResource;
use App\MoonShine\Resources\KategoriResource;
use App\MoonShine\Resources\PengaturanResource;
use App\MoonShine\Resources\PenggunaResource;
use App\MoonShine\Resources\ResourceDasar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Core\Core;
use MoonShine\Core\TypeCasts\MixedDataWrapper;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Support\Enums\Action;

uses(RefreshDatabase::class);

function buatSemuaResource(): array
{
    /** @var MoonShine $core */
    $core = app()->make(MoonShine::class);
    $resources = [
        new DoaResource($core),
        new KataDoaResource($core),
        new KategoriResource($core),
        new PengaturanResource($core),
        new PenggunaResource($core),
    ];
    $core->resources($resources, newCollection: true);
    Core::setInstance($core);

    return [$core, $resources];
}

function panggilProtectedResource(object $object, string $method): mixed
{
    return (new ReflectionMethod($object, $method))->invoke($object);
}

it('memuat setiap resource dengan field index dan form', function () {
    [, $resources] = buatSemuaResource();

    foreach ($resources as $resource) {
        expect($resource->getIndexFields())->not->toBeEmpty();
        expect($resource->getFormFields())->not->toBeEmpty();
        expect($resource->getDetailFields())->not->toBeEmpty();
    }
});

it('menolak slug route acak pada validasi resource doa', function () {
    $resource = app()->make(DoaResource::class);
    $item = new MixedDataWrapper([], null);

    $rules = $resource->aturan($item);

    expect($rules['slug'])->toContain('not_in:acak');
    expect($rules['judul'][0])->toBe('required');
});

it('menerapkan aturan validasi berbeda untuk pengaturan', function () {
    $resource = app()->make(PengaturanResource::class);

    $toggleRules = $resource->aturan(new MixedDataWrapper(['kunci' => 'doa_acak_hanya_terverifikasi']));
    $textRules = $resource->aturan(new MixedDataWrapper(['kunci' => 'terjemahan_perkata_api_token']));

    expect($toggleRules['nilai'])->toBe(['required', 'in:0,1']);
    expect($textRules['nilai'])->toBe(['nullable', 'string']);
});

it('mewajibkan nama kategori dan slug unik', function () {
    $resource = app()->make(KategoriResource::class);

    $rules = $resource->aturan(new MixedDataWrapper([], null));

    expect($rules['nama'])->toBe(['required', 'string', 'max:255']);
    expect($rules['slug'][0])->toBe('required');
});

it('membangun field form dan aturan validasi dari resource', function () {
    [$core, $resources] = buatSemuaResource();
    $page = new class($core) extends FormHalaman
    {
        public function resolveFields(): iterable
        {
            return $this->fields();
        }

        public function resolveRules(MixedDataWrapper $item): array
        {
            return $this->rules($item);
        }
    };
    $page->setResource($resources[0]);

    expect(iterator_to_array($page->resolveFields()))->toHaveCount(2);
    expect($page->resolveRules(new MixedDataWrapper([], null))['judul'][0])->toBe('required');
});

it('menghitung jumlah doa pada query resource kategori', function () {
    $kategori = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 1]);
    Doa::create([
        'kategori_id' => $kategori->id,
        'judul' => 'Doa Harian',
        'slug' => 'doa-harian',
        'teks_arab' => 'نَصٌّ',
        'terjemahan' => 'Teks',
    ]);
    $resource = app()->make(KategoriResource::class);
    $method = new ReflectionMethod($resource, 'modifyQueryBuilder');

    $results = $method->invoke($resource, Kategori::query())->get();

    expect($results->first()->doa_count)->toBe(1);
});

it('mengekspos field cari filter dan aksi read-only', function () {
    $doa = app()->make(DoaResource::class);
    $kataDoa = app()->make(KataDoaResource::class);
    $kategori = app()->make(KategoriResource::class);
    $pengaturan = app()->make(PengaturanResource::class);
    $pengguna = app()->make(PenggunaResource::class);

    expect(panggilProtectedResource($doa, 'search'))->toBe(['judul', 'teks_arab', 'transliterasi']);
    expect(panggilProtectedResource($kataDoa, 'search'))->toBe(['kata_arab', 'arti_kata']);
    expect(panggilProtectedResource($kataDoa, 'filters'))->not->toBeEmpty();
    expect(panggilProtectedResource($kategori, 'search'))->toBe(['nama', 'slug']);
    expect(panggilProtectedResource($pengguna, 'search'))->toBe(['nama', 'email']);
    expect(panggilProtectedResource($pengaturan, 'activeActions')->toArray())->toBe([Action::UPDATE]);
    expect(panggilProtectedResource($pengguna, 'activeActions')->toArray())->toBe([Action::VIEW]);
});

it('menyediakan default kosong dan halaman CRUD bersama pada resource dasar', function () {
    /** @var MoonShine $core */
    $core = app()->make(MoonShine::class);
    $resource = new class($core) extends ResourceDasar {};

    expect(iterator_to_array($resource->indexFields()))->toBe([]);
    expect(iterator_to_array($resource->formFields()))->toBe([]);
    expect(iterator_to_array($resource->detailFields()))->toBe([]);
    expect($resource->aturan(new MixedDataWrapper([])))->toBe([]);
    expect(panggilProtectedResource($resource, 'pages'))->toBe(
        [
            IndexHalaman::class,
            FormHalaman::class,
            DetailHalaman::class,
        ]
    );
});
