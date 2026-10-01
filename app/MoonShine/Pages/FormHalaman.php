<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;

/** Halaman form generik: field & aturan validasi diambil dari resource. */
class FormHalaman extends FormPage
{
    protected function fields(): iterable
    {
        return $this->getResource()->formFields();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return $this->getResource()->aturan($item);
    }
}
