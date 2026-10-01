<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\MoonShine\Pages\DetailHalaman;
use App\MoonShine\Pages\FormHalaman;
use App\MoonShine\Pages\IndexHalaman;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * Basis semua resource: cukup definisikan indexFields(), formFields(),
 * detailFields() dan aturan() — halaman CRUD dipakai bersama.
 */
abstract class ResourceDasar extends ModelResource
{
    protected function pages(): array
    {
        return [IndexHalaman::class, FormHalaman::class, DetailHalaman::class];
    }

    abstract public function indexFields(): iterable;

    abstract public function formFields(): iterable;

    public function detailFields(): iterable
    {
        return $this->indexFields();
    }

    public function aturan(DataWrapperContract $item): array
    {
        return [];
    }
}
