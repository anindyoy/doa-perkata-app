<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;

/** Halaman index generik: daftar field diambil dari $resource->indexFields(). */
class IndexHalaman extends IndexPage
{
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields();
    }
}
