<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SkinConcern: string implements HasLabel
{
    case Acne = 'acne';
    case Dullness = 'dullness';
    case DarkSpots = 'dark_spots';
    case Aging = 'aging';
    case Dehydration = 'dehydration';
    case LargePores = 'large_pores';
    case Redness = 'redness';
    case ExcessOil = 'excess_oil';

    public function getLabel(): string
    {
        return __('admin.skin_concern.'.$this->value);
    }
}
