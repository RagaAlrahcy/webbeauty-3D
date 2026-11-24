<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MarketplacePlatform: string implements HasLabel
{
    case Shopee = 'shopee';
    case Tokopedia = 'tokopedia';
    case TiktokShop = 'tiktok_shop';
    case Lazada = 'lazada';
    case Blibli = 'blibli';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('admin.platform.'.$this->value);
    }
}
