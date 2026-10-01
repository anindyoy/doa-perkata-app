<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Pengguna;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

#[Icon('users')]
#[Order(4)]
class PenggunaResource extends ResourceDasar
{
    protected string $model = Pengguna::class;

    protected string $title = 'Pengguna';

    protected string $column = 'nama';

    protected function activeActions(): ListOf
    {
        return new ListOf(Action::class, [Action::VIEW]); // read-only
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Nama', 'nama'),
            Text::make('Email', 'email'),
            Date::make('Terdaftar', 'dibuat_pada')->format('d M Y H:i'),
        ];
    }

    public function formFields(): iterable
    {
        return $this->indexFields();
    }

    protected function search(): array
    {
        return ['nama', 'email'];
    }
}
