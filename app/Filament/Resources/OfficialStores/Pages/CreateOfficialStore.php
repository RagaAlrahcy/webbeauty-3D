<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfficialStores\Pages;

use App\Filament\Resources\OfficialStores\OfficialStoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficialStore extends CreateRecord
{
    protected static string $resource = OfficialStoreResource::class;
}
