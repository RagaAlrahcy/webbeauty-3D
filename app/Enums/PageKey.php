<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Fixed content pages edited in the CMS. Each key has its own public route.
 */
enum PageKey: string implements HasLabel
{
    case About = 'about';
    case Privacy = 'privacy';
    case Terms = 'terms';
    case Reseller = 'reseller';

    public function getLabel(): string
    {
        return __('admin.page_key.'.$this->value);
    }

    /** Base route name, used with localized_route(). */
    public function routeName(): string
    {
        return match ($this) {
            self::About => 'about',
            self::Privacy => 'privacy',
            self::Terms => 'terms',
            self::Reseller => 'reseller',
        };
    }
}
