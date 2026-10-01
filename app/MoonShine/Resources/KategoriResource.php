<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Kategori;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

#[Icon('tag')]
#[Order(2)]
class KategoriResource extends ResourceDasar
{
    protected string $model = Kategori::class;

    protected string $title = 'Kategori';

    protected string $column = 'nama';

    protected string $sortColumn = 'urutan';

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->withCount('doa'); // jumlah doa per kategori
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Nama', 'nama')->sortable(),
            Text::make('Slug', 'slug'),
            Number::make('Urutan', 'urutan')->sortable(),
            Number::make('Jumlah Doa', 'doa_count'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Nama', 'nama')->required(),
            Slug::make('Slug', 'slug')->from('nama')->required(),
            Number::make('Urutan', 'urutan')->default(0),
        ];
    }

    public function aturan(DataWrapperContract $item): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('kategori', 'slug')->ignore($item->getKey())],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function search(): array
    {
        return ['nama', 'slug'];
    }
}
