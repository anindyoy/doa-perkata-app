<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\KataDoa;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

#[Icon('language')]
#[Order(3)]
class KataDoaResource extends ResourceDasar
{
    protected string $model = KataDoa::class;

    protected string $title = 'Kata Doa (Review)';

    protected string $column = 'kata_arab';

    protected array $with = ['doa'];

    private const STATUS = [
        'diterjemahkan_ai' => 'Diterjemahkan AI',
        'terverifikasi' => 'Terverifikasi',
    ];

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Doa', 'doa', formatted: 'judul', resource: DoaResource::class),
            Text::make('Kata Arab', 'kata_arab'),
            Text::make('Arti', 'arti_kata'),
            Number::make('Urutan', 'urutan')->sortable(),
            Select::make('Status', 'status')
                ->options(self::STATUS)
                ->badge(fn ($value) => $value === 'terverifikasi' ? Color::SUCCESS : Color::WARNING),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Doa', 'doa', formatted: 'judul', resource: DoaResource::class)->required(),
            Number::make('Urutan', 'urutan')->default(1),
            Text::make('Kata Arab', 'kata_arab')->required(),
            Text::make('Transliterasi', 'transliterasi_kata'),
            Text::make('Arti', 'arti_kata')->required(),
            Select::make('Status', 'status')->options(self::STATUS)->default('diterjemahkan_ai'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Status', 'status')->options(self::STATUS)->nullable(),
        ];
    }

    protected function search(): array
    {
        return ['kata_arab', 'arti_kata'];
    }
}
