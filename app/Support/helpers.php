<?php

declare(strict_types=1);

use App\Support\Locales;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

if (! function_exists('localized_route')) {
    /**
     * @param  array<string, mixed>  $parameters
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        return Locales::route($name, $parameters, $locale);
    }
}

if (! function_exists('media_url')) {
    /**
     * URL of a media conversion, falling back to the original file while the
     * queued conversion has not been generated yet.
     */
    function media_url(?Media $media, string $conversion = ''): ?string
    {
        if ($media === null) {
            return null;
        }

        if ($conversion !== '' && $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl($conversion);
        }

        return $media->getUrl();
    }
}
