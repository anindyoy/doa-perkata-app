<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Resources\DoaResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Support\ListOf;

/** Form tambah/edit doa + tombol generate arti per kata saat edit. */
class DoaFormHalaman extends FormHalaman
{
    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        $sumber = $this->getResource();

        return new ListOf(ActionButtonContract::class, [
            ...parent::buttons()->toArray(),
            ...($sumber instanceof DoaResource ? [$sumber->tombolGenerateForm()] : []),
        ]);
    }
}
