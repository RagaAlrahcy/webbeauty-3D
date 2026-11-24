<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SkinType: string implements HasLabel
{
    case Normal = 'normal';
    case Oily = 'oily';
    case Dry = 'dry';
    case Combination = 'combination';
    case Sensitive = 'sensitive';

    public function getLabel(): string
    {
        return __('admin.skin_type.'.$this->value);
    }
}
