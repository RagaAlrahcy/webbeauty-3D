<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StoreType: string implements HasLabel
{
    case Marketplace = 'marketplace';
    case Offline = 'offline';

    public function getLabel(): string
    {
        return __('admin.store_type.'.$this->value);
    }
}
