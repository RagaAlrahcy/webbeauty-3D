<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContainerShape: string implements HasLabel
{
    case Bottle = 'bottle';
    case Jar = 'jar';
    case Tube = 'tube';
    case Dropper = 'dropper';
    case Compact = 'compact';

    public function getLabel(): string
    {
        return __('admin.container_shape.'.$this->value);
    }
}
