<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;

/**
 * Side-by-side ID/EN inputs for spatie/laravel-translatable attributes.
 *
 * The attribute state is an array keyed by locale (e.g. `name.id`, `name.en`),
 * which HasTranslations::setAttribute() stores as all translations at once.
 */
class Translatable
{
    /** @var array<string, string> */
    public const array LOCALES = ['id' => 'ID', 'en' => 'EN'];

    public const string DEFAULT_LOCALE = 'id';

    /**
     * @param  Closure(string $statePath): Field  $factory
     */
    public static function make(
        string $attribute,
        string $label,
        Closure $factory,
        bool $required = false,
        int $columns = 2,
    ): Grid {
        $fields = [];

        foreach (self::LOCALES as $locale => $localeLabel) {
            $field = $factory("{$attribute}.{$locale}")
                ->label("{$label} ({$localeLabel})");

            if ($locale === self::DEFAULT_LOCALE) {
                $field->required($required);
            } else {
                $field->helperText(__('admin.translation_fallback_hint'));
            }

            $fields[] = $field;
        }

        return Grid::make($columns)->schema($fields)->columnSpanFull();
    }

    /**
     * Replace the current-locale strings that attributesToArray() returns with
     * every translation of each translatable attribute, for filling a form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data, ?Model $record): array
    {
        if ($record === null || ! method_exists($record, 'getTranslatableAttributes')) {
            return $data;
        }

        foreach ($record->getTranslatableAttributes() as $attribute) {
            $translations = $record->getTranslations($attribute);

            foreach (array_keys(self::LOCALES) as $locale) {
                $translations[$locale] ??= null;
            }

            $data[$attribute] = $translations;
        }

        return $data;
    }
}
