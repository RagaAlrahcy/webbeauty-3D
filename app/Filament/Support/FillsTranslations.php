<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * For EditRecord pages of models using HasTranslations.
 */
trait FillsTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Translatable::fill($data, $this->getRecord());
    }
}
