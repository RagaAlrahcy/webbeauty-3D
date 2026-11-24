<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductSort: string implements HasLabel
{
    case Recommended = 'recommended';
    case Newest = 'newest';
    case PriceLow = 'price_low';
    case PriceHigh = 'price_high';

    public function getLabel(): string
    {
        return __('admin.sort.'.$this->value);
    }
}
