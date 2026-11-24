<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResellerBusinessType: string implements HasLabel
{
    case OnlineShop = 'online_shop';
    case OfflineStore = 'offline_store';
    case Dropshipper = 'dropshipper';
    case Personal = 'personal';

    public function getLabel(): string
    {
        return __('admin.business_type.'.$this->value);
    }
}
