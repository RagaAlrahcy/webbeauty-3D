<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ResellerBusinessType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Reseller program application sent from the public form. */
#[Fillable(['name', 'phone', 'email', 'city', 'business_type', 'shop_url', 'message', 'status', 'notes', 'locale'])]
class ResellerApplication extends Model
{
    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'business_type' => ResellerBusinessType::class,
            'status' => ApplicationStatus::class,
        ];
    }
}
