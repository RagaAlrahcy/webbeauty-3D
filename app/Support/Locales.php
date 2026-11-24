<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

/** Supported public-site locales and URL helpers (AGENTS.md section 6.6). */
final class Locales
{
    public const string DEFAULT = 'id';

    /** @var array<string, array{label: string, hreflang: string, og: string}> */
    public const array SUPPORTED = [
        'id' => ['label' => 'Indonesia', 'hreflang' => 'id', 'og' => 'id_ID'],
        'en' => ['label' => 'English', 'hreflang' => 'en', 'og' => 'en_US'],
    ];

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::SUPPORTED);
    }

    /** Route name for a locale, e.g. products.show => en.products.show. */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $base = str_starts_with($name, 'en.') ? substr($name, 3) : $name;

        return $locale === self::DEFAULT ? $base : "{$locale}.{$base}";
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?string $locale = null): string
    {
        return route(self::routeName($name, $locale), $parameters);
    }

    /**
     * URL of the current page in another locale (falls back to that locale's home).
     */
    public static function currentUrlFor(string $locale): string
    {
        $route = Route::current();
        $name = $route?->getName();

        if ($name === null || ! Route::has(self::routeName($name, $locale))) {
            return self::route('home', [], $locale);
        }

        $url = self::route($name, $route->parameters(), $locale);
        $query = request()->getQueryString();

        return $query ? "{$url}?{$query}" : $url;
    }
}
