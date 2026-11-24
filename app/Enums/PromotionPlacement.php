<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PromotionPlacement: string implements HasLabel
{
    case TopBar = 'top_bar';
    case Home = 'home';

    public function getLabel(): string
    {
        return __('admin.promotion_placement.'.$this->value);
    }
}
