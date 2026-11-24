<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfficialStores\Pages;

use App\Filament\Resources\OfficialStores\OfficialStoreResource;
use App\Filament\Support\FillsTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficialStore extends EditRecord
{
    use FillsTranslations;

    protected static string $resource = OfficialStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
