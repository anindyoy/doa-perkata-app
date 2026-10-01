<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;

class DetailHalaman extends DetailPage
{
    protected function fields(): iterable
    {
        return $this->getResource()->detailFields();
    }
}
