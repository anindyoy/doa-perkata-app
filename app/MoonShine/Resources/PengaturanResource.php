<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Pengaturan;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

#[Icon('cog-6-tooth')]
#[Order(5)]
class PengaturanResource extends ResourceDasar
{
    protected string $model = Pengaturan::class;

    protected string $title = 'Pengaturan';

    protected string $column = 'kunci';

    /** Hanya edit nilai; baris dibuat lewat migration. */
    protected function activeActions(): ListOf
    {
        return new ListOf(Action::class, [Action::UPDATE]);
    }

    private function opsi(): array
    {
        return ['1' => 'Aktif', '0' => 'Nonaktif'];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Kunci', 'kunci'),
            Text::make('Nilai', 'nilai_tampil'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Kunci', 'kunci')->readonly(),
            Select::make('Doa Acak hanya ambil doa yang sudah terverifikasi', 'nilai')
                ->options($this->opsi())
                ->required()
                ->showWhen('kunci', '=', 'doa_acak_hanya_terverifikasi'),
            Text::make('Token API', 'nilai')
                ->type('password')
                ->showWhen('kunci', '=', 'terjemahan_perkata_api_token'),
            Text::make('Nilai', 'nilai')
                ->showWhen('kunci', '!=', 'doa_acak_hanya_terverifikasi')
                ->showWhen('kunci', '!=', 'terjemahan_perkata_api_token'),
        ];
    }

    public function aturan(DataWrapperContract $item): array
    {
        if ($item->get('kunci') === 'doa_acak_hanya_terverifikasi') {
            return ['nilai' => ['required', 'in:0,1']];
        }

        return ['nilai' => ['nullable', 'string']];
    }
}
