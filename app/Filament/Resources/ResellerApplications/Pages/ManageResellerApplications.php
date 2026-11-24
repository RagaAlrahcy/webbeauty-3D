<?php

declare(strict_types=1);

namespace App\Filament\Resources\ResellerApplications\Pages;

use App\Filament\Resources\ResellerApplications\ResellerApplicationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageResellerApplications extends ManageRecords
{
    protected static string $resource = ResellerApplicationResource::class;
}
