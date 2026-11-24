<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductClaim: string implements HasLabel
{
    case CrueltyFree = 'cruelty_free';
    case Vegan = 'vegan';
    case DermatologicallyTested = 'dermatologically_tested';
    case NonComedogenic = 'non_comedogenic';
    case Hypoallergenic = 'hypoallergenic';
    case FragranceFree = 'fragrance_free';
    case AlcoholFree = 'alcohol_free';
    case ParabenFree = 'paraben_free';

    public function getLabel(): string
    {
        return __('admin.claim.'.$this->value);
    }
}
