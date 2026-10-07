<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Layouts\AdminLayout;
use App\MoonShine\Resources\DoaResource;
use App\MoonShine\Resources\KataDoaResource;
use App\MoonShine\Resources\KategoriResource;
use App\MoonShine\Resources\PengaturanResource;
use App\MoonShine\Resources\PenggunaResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                DoaResource::class,
                KataDoaResource::class,
                KategoriResource::class,
                PengaturanResource::class,
                PenggunaResource::class,
            ])
            ->pages([...$core->getConfig()->getPages()])
            ->getConfig()
            ->layout(AdminLayout::class);
    }
}
