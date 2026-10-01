<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class MoonShineResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_each_resource_with_index_and_form_fields(): void
    {
        $core = $this->app->make(MoonShine::class);
        $resources = [
            new DoaResource($core),
            new KataDoaResource($core),
            new KategoriResource($core),
            new PengaturanResource($core),
            new PenggunaResource($core),
        ];
        $core->resources($resources, newCollection: true);
        Core::setInstance($core);

        foreach ($resources as $resource) {
            $this->assertNotEmpty($resource->getIndexFields());
            $this->assertNotEmpty($resource->getFormFields());
            $this->assertNotEmpty($resource->getDetailFields());
        }
    }

    public function test_doa_resource_validation_rejects_the_random_route_slug(): void
    {
        $resource = $this->app->make(DoaResource::class);
        $item = new MixedDataWrapper([], null);

        $rules = $resource->aturan($item);

        $this->assertContains('not_in:acak', $rules['slug']);
        $this->assertSame('required', $rules['judul'][0]);
    }

    public function test_it_applies_distinct_validation_rules_to_settings(): void
    {
        $resource = $this->app->make(PengaturanResource::class);

        $toggleRules = $resource->aturan(new MixedDataWrapper(['kunci' => 'doa_acak_hanya_terverifikasi']));
        $textRules = $resource->aturan(new MixedDataWrapper(['kunci' => 'terjemahan_perkata_api_token']));

        $this->assertSame(['required', 'in:0,1'], $toggleRules['nilai']);
        $this->assertSame(['nullable', 'string'], $textRules['nilai']);
    }

    public function test_it_requires_category_names_and_unique_slugs(): void
    {
        $resource = $this->app->make(KategoriResource::class);

        $rules = $resource->aturan(new MixedDataWrapper([], null));

        $this->assertSame(['required', 'string', 'max:255'], $rules['nama']);
        $this->assertSame('required', $rules['slug'][0]);
    }

    public function test_it_builds_form_fields_and_validation_rules_from_its_resource(): void
    {
        $core = $this->app->make(MoonShine::class);
        $resources = [
            new DoaResource($core),
            new KataDoaResource($core),
            new KategoriResource($core),
            new PengaturanResource($core),
            new PenggunaResource($core),
        ];
        $core->resources($resources, newCollection: true);
        Core::setInstance($core);
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

        $this->assertCount(2, iterator_to_array($page->resolveFields()));
        $this->assertSame('required', $page->resolveRules(new MixedDataWrapper([], null))['judul'][0]);
    }

    public function test_category_resource_query_includes_the_prayer_count(): void
    {
        $kategori = Kategori::create(['nama' => 'Harian', 'slug' => 'harian', 'urutan' => 1]);
        Doa::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Doa Harian',
            'slug' => 'doa-harian',
            'teks_arab' => 'نَصٌّ',
            'terjemahan' => 'Teks',
        ]);
        $resource = $this->app->make(KategoriResource::class);
        $method = new \ReflectionMethod($resource, 'modifyQueryBuilder');

        $results = $method->invoke($resource, Kategori::query())->get();

        $this->assertSame(1, $results->first()->doa_count);
    }

    public function test_it_exposes_configured_search_fields_filters_and_read_only_actions(): void
    {
        $doa = $this->app->make(DoaResource::class);
        $kataDoa = $this->app->make(KataDoaResource::class);
        $kategori = $this->app->make(KategoriResource::class);
        $pengaturan = $this->app->make(PengaturanResource::class);
        $pengguna = $this->app->make(PenggunaResource::class);

        $this->assertSame(['judul', 'teks_arab', 'transliterasi'], $this->invokeProtected($doa, 'search'));
        $this->assertSame(['kata_arab', 'arti_kata'], $this->invokeProtected($kataDoa, 'search'));
        $this->assertNotEmpty($this->invokeProtected($kataDoa, 'filters'));
        $this->assertSame(['nama', 'slug'], $this->invokeProtected($kategori, 'search'));
        $this->assertSame(['nama', 'email'], $this->invokeProtected($pengguna, 'search'));
        $this->assertSame([Action::UPDATE], $this->invokeProtected($pengaturan, 'activeActions')->toArray());
        $this->assertSame([Action::VIEW], $this->invokeProtected($pengguna, 'activeActions')->toArray());
    }

    public function test_base_resource_provides_empty_defaults_and_shared_crud_pages(): void
    {
        $core = $this->app->make(MoonShine::class);
        $resource = new class($core) extends ResourceDasar {};

        $this->assertSame([], iterator_to_array($resource->indexFields()));
        $this->assertSame([], iterator_to_array($resource->formFields()));
        $this->assertSame([], iterator_to_array($resource->detailFields()));
        $this->assertSame([], $resource->aturan(new MixedDataWrapper([])));
        $this->assertSame(
            [
                IndexHalaman::class,
                FormHalaman::class,
                DetailHalaman::class,
            ],
            $this->invokeProtected($resource, 'pages')
        );
    }

    private function invokeProtected(object $object, string $method): mixed
    {
        return (new \ReflectionMethod($object, $method))->invoke($object);
    }
}
