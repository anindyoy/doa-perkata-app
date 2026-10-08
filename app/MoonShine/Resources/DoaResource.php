<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Doa;
use App\MoonShine\Pages\DetailHalaman;
use App\MoonShine\Pages\DoaFormHalaman;
use App\MoonShine\Pages\DoaIndexHalaman;
use App\Services\GeneratorKataDoa;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Laravel\Fields\Slug;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\Color;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Components\ActionButton;
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

    protected function pages(): array
    {
        return [DoaIndexHalaman::class, DoaFormHalaman::class, DetailHalaman::class];
    }

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

    /**
     * Tombol baris di halaman daftar: generate arti per kata via AI
     * memakai model & URL dari menu Pengaturan.
     */
    public function tombolGenerateIndex(): ActionButtonContract
    {
        $segar = [$this->getListEventName()];

        return ActionButton::make('Generate arti')
            ->method(
                'generateKata',
                message: 'Arti per kata berhasil digenerate.',
                events: $segar,
                page: $this->getIndexPage(),
                resource: $this,
            )
            ->async(method: HttpMethod::POST, events: $segar)
            ->withConfirm(
                title: 'Generate arti per kata?',
                content: 'Kata yang sudah ada (termasuk yang terverifikasi) akan dihapus dan dibuat ulang oleh AI memakai model & URL dari Pengaturan.',
                button: 'Generate',
                method: HttpMethod::POST,
            )
            ->icon('sparkles')
            ->warning()
            ->showInLine()
            ->canSee(fn (mixed $asli, ?DataWrapperContract $data): bool => (bool) $data?->getKey()
                && $this->setItem($asli)->can(Ability::UPDATE));
    }

    /**
     * Tombol di halaman form edit: generate arti per kata via AI
     * memakai model & URL dari menu Pengaturan.
     */
    public function tombolGenerateForm(): ActionButtonContract
    {
        return ActionButton::make('Generate arti per kata')
            ->method(
                'generateKata',
                message: 'Arti per kata berhasil digenerate.',
                page: $this->getFormPage(),
                resource: $this,
            )
            ->async(method: HttpMethod::POST)
            ->withConfirm(
                title: 'Generate arti per kata?',
                content: 'Simpan dulu perubahan teks Arab bila ada. Kata yang sudah ada (termasuk yang terverifikasi) akan dihapus dan dibuat ulang oleh AI.',
                button: 'Generate',
                method: HttpMethod::POST,
            )
            ->icon('sparkles')
            ->warning()
            ->canSee(fn (mixed $asli, ?DataWrapperContract $data): bool => (bool) $data?->getKey()
                && $this->setItem($asli)->can(Ability::UPDATE));
    }

    /**
     * Dipanggil tombol admin (index & form) lewat MethodController.
     * Memakai model & URL AI yang ada di Pengaturan.
     */
    #[AsyncMethod]
    public function generateKata(GeneratorKataDoa $generator)
    {
        $id = request()->input('resourceItem', $this->getItemID());

        /** @var Doa|null $doa */
        $doa = $id ? Doa::query()->find($id) : null;

        if (! $doa) {
            toast('Doa tidak ditemukan.', ToastType::ERROR);

            return null;
        }

        try {
            $jumlah = $generator->generate($doa);
        } catch (\Throwable $e) {
            toast($e->getMessage(), ToastType::ERROR);

            return null;
        }

        toast("Berhasil generate {$jumlah} kata (status: diterjemahkan_ai).", ToastType::SUCCESS);

        // Dari form edit: muat ulang halaman edit agar repeater kata tampil baru.
        $pageUri = (string) (moonshineRequest()->getPageUri() ?? '');
        if ($pageUri !== '' && str_contains($pageUri, 'form')) {
            return redirect($this->getFormPageUrl($id));
        }

        return null;
    }
}
