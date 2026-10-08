<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Pengaturan;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * Halaman pengaturan berbentuk satu form input (bukan tabel):
 * semua kunci pengaturan ditampilkan sekaligus dalam satu layar.
 */
#[Icon('cog-6-tooth')]
#[Order(5)]
class PengaturanHalaman extends Page
{
    protected string $title = 'Pengaturan';

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /** Aturan validasi form pengaturan. */
    public static function aturanSimpan(): array
    {
        return [
            'doa_acak_hanya_terverifikasi' => ['required', 'in:0,1'],
            'terjemahan_perkata_api_url' => ['nullable', 'string', 'max:2048'],
            'terjemahan_perkata_api_token' => ['nullable', 'string', 'max:4096'],
            'terjemahan_perkata_model' => ['nullable', 'string', 'max:255'],
            'terjemahan_perkata_max_tokens' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /** Nilai awal form diambil dari tabel pengaturan. */
    public static function nilaiAwal(): array
    {
        $nilai = Pengaturan::query()->pluck('nilai', 'kunci')->all();

        return [
            'doa_acak_hanya_terverifikasi' => $nilai['doa_acak_hanya_terverifikasi'] ?? '1',
            'terjemahan_perkata_api_url' => $nilai['terjemahan_perkata_api_url'] ?? null,
            'terjemahan_perkata_model' => $nilai['terjemahan_perkata_model'] ?? null,
            'terjemahan_perkata_api_token' => $nilai['terjemahan_perkata_api_token'] ?? null,
            'terjemahan_perkata_max_tokens' => $nilai['terjemahan_perkata_max_tokens'] ?? null,
        ];
    }

    /** Simpan semua nilai form ke tabel pengaturan (baris dibuat bila belum ada). */
    public static function simpanNilai(array $data): void
    {
        foreach (array_keys(self::aturanSimpan()) as $kunci) {
            if (! array_key_exists($kunci, $data)) {
                continue;
            }

            $nilai = $data[$kunci];
            $nilai = $nilai === '' || $nilai === null ? null : (string) $nilai;

            Pengaturan::query()->updateOrCreate(
                ['kunci' => $kunci],
                ['nilai' => $nilai]
            );
        }
    }

    /**
     * Dipanggil form pengaturan lewat MethodController.
     */
    #[AsyncMethod]
    public function simpan()
    {
        $data = request()->validate(self::aturanSimpan());

        self::simpanNilai($data);

        toast('Pengaturan disimpan.', ToastType::SUCCESS);

        return null;
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            $this->getForm(),
        ];
    }

    public function getForm(): FormBuilderContract
    {
        return FormBuilder::make()
            ->asyncMethod('simpan', message: 'Pengaturan disimpan.', page: $this)
            ->name('pengaturan-form')
            ->fields([
                Box::make('Doa Acak', [
                    Grid::make([
                        Column::make([
                            Select::make('Hanya ambil doa yang sudah terverifikasi', 'doa_acak_hanya_terverifikasi')
                                ->options(['1' => 'Aktif', '0' => 'Nonaktif'])
                                ->required()
                                ->hint('Bila aktif, menu Doa Acak hanya memilih doa yang seluruh katanya terverifikasi.'),
                        ], 6),
                    ]),
                ]),
                Box::make('Terjemahan Per Kata (AI)', [
                    Grid::make([
                        Column::make([
                            Text::make('URL API', 'terjemahan_perkata_api_url')
                                ->hint('Contoh: https://api.openai.com/v1/chat/completions'),
                        ], 6),
                        Column::make([
                            Text::make('Token API', 'terjemahan_perkata_api_token')
                                ->customAttributes(['type' => 'password'])
                                ->eye(),
                        ], 6),
                        Column::make([
                            Text::make('Model', 'terjemahan_perkata_model')
                                ->hint('Contoh: gpt-4o-mini'),
                        ], 6),
                        Column::make([
                            Number::make('Max Tokens', 'terjemahan_perkata_max_tokens')
                                ->min(1),
                        ], 6),
                    ]),
                ]),
            ])
            ->fill(self::nilaiAwal())
            ->submit('Simpan Pengaturan', ['class' => 'btn-primary btn-lg']);
    }
}
