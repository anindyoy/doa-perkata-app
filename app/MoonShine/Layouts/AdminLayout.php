<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\Laravel\Layouts\AppLayout;

class AdminLayout extends AppLayout
{
    /**
     * @return list<MenuElementContract>
     */
    protected function menu(): array
    {
        return [
            ...$this->menuAutoloader->resolve(),
            ...parent::menu(),
        ];
    }
}
