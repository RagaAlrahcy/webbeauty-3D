<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Material: string implements HasLabel
{
    case Glossy = 'glossy';
    case Matte = 'matte';
    case FrostedGlass = 'frosted_glass';
    case Metallic = 'metallic';

    public function getLabel(): string
    {
        return __('admin.material.'.$this->value);
    }
}
