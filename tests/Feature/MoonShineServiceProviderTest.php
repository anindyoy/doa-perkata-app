<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class MoonShineServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_all_application_resources_with_moonshine(): void
    {
        $core = $this->app->make(CoreContract::class);
        $resources = $core
            ->getResources()
            ->map(fn ($resource): string => $resource::class)
            ->all();

        $this->assertSame([
            DoaResource::class,
            KataDoaResource::class,
            KategoriResource::class,
            PengaturanResource::class,
            PenggunaResource::class,
        ], $resources);

        $this->assertSame(AdminLayout::class, $core->getConfig()->getLayout());
    }

    public function test_authenticated_admin_sees_all_application_resources_in_the_menu(): void
    {
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
    }
}
