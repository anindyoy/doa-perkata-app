<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Doa;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

#[Icon('book-open')]
#[Order(1)]
class DoaResource extends ResourceDasar
{
    protected string $model = Doa::class;

    protected string $title = 'Doa';

    protected string $column = 'judul';

    protected array $with = ['kategori'];

    protected string $sortColumn = 'urutan';

    protected SortDirection $sortDirection = SortDirection::ASC;

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Judul', 'judul')->sortable(),
            BelongsTo::make('Kategori', 'kategori', formatted: 'nama', resource: KategoriResource::class),
            Number::make('Urutan', 'urutan')->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Judul', 'judul')->required(),
                Slug::make('Slug', 'slug')->from('judul')->required(),
                BelongsTo::make('Kategori', 'kategori', formatted: 'nama', resource: KategoriResource::class)->nullable(),
                Number::make('Urutan', 'urutan')->default(0),
                Textarea::make('Teks Arab', 'teks_arab')->required(),
                Textarea::make('Transliterasi', 'transliterasi'),
                Textarea::make('Terjemahan', 'terjemahan')->required(),
                Text::make('Catatan', 'catatan'),
                Text::make('Referensi Sumber', 'referensi_sumber'),
            ]),
            Box::make('Arti per Kata', [
                RelationRepeater::make('Kata per Kata', 'kataDoa', resource: KataDoaResource::class)
                    ->fields([
                        ID::make(),
                        Number::make('Urutan', 'urutan')->default(1),
                        Text::make('Kata Arab', 'kata_arab')->required(),
                        Text::make('Transliterasi', 'transliterasi_kata'),
                        Text::make('Arti', 'arti_kata')->required(),
                        Select::make('Status', 'status')
                            ->options([
                                'diterjemahkan_ai' => 'Diterjemahkan AI',
                                'terverifikasi' => 'Terverifikasi',
                            ])
                            ->default('diterjemahkan_ai')
                            ->badge(fn ($value) => $value === 'terverifikasi' ? Color::SUCCESS : Color::WARNING),
                    ])
                    ->creatable()
                    ->removable(),
            ]),
        ];
    }

    public function aturan(DataWrapperContract $item): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'not_in:acak',
                Rule::unique('doa', 'slug')->ignore($item->getKey()),
            ],
            'kategori_id' => ['nullable', 'exists:kategori,id'],
            'teks_arab' => ['required', 'string'],
            'terjemahan' => ['required', 'string'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function search(): array
    {
        return ['judul', 'teks_arab', 'transliterasi'];
    }
}
