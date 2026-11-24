<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'brand_name', 'tagline', 'primary_color', 'accent_color', 'whatsapp_number',
    'marketplace_url', 'instagram_url', 'tiktok_url', 'email', 'phone', 'address',
])]
class SiteSetting extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    public const string CACHE_KEY = 'site_settings';

    /** @var list<string> */
    public array $translatable = ['tagline'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** The single settings row (cached), created with defaults when missing. */
    public static function current(): self
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): self => static::query()->with('media')->first()
                ?? static::query()->create(['brand_name' => config('app.name')]),
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        $number = (string) preg_replace('/\D+/', '', $this->whatsapp_number);
        $number = str_starts_with($number, '0') ? '62'.substr($number, 1) : $number;

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }
}
