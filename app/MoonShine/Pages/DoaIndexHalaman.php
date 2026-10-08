<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Resources\DoaResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Support\ListOf;

/** Daftar doa + aksi generate arti per kata di tiap baris. */
class DoaIndexHalaman extends IndexHalaman
{
    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        $sumber = $this->getResource();

        return new ListOf(ActionButtonContract::class, [
            ...parent::buttons()->toArray(),
            ...($sumber instanceof DoaResource ? [$sumber->tombolGenerateIndex()] : []),
        ]);
    }
}
