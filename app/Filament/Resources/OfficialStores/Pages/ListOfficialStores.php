<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfficialStores\Pages;

use App\Filament\Resources\OfficialStores\OfficialStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOfficialStores extends ListRecords
{
    protected static string $resource = OfficialStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
