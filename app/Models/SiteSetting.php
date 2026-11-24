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
    'ga4_measurement_id', 'meta_pixel_id',
])]
class SiteSetting extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    public const string CACHE_KEY = 'site_settings';

    /** Google Analytics 4 measurement ID, e.g. G-AB12CD34EF. */
    public const string GA4_ID_PATTERN = '/^G-[A-Z0-9]{4,15}$/';

    /** Meta (Facebook) Pixel ID: digits only. */
    public const string META_PIXEL_ID_PATTERN = '/^\d{6,20}$/';

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
        return self::whatsappLink($this->whatsapp_number, $message);
    }

    /** wa.me link for an Indonesian number written as 08xx, 628xx or +62 8xx. */
    public static function whatsappLink(?string $phone, ?string $message = null): ?string
    {
        $number = (string) preg_replace('/\D+/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        $number = str_starts_with($number, '0') ? '62'.substr($number, 1) : $number;

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    /** GA4 ID safe to print inside a script tag, or null when unset/invalid. */
    public function ga4Id(): ?string
    {
        $id = (string) $this->ga4_measurement_id;

        return preg_match(self::GA4_ID_PATTERN, $id) ? $id : null;
    }

    /** Meta Pixel ID safe to print inside a script tag, or null when unset/invalid. */
    public function metaPixelId(): ?string
    {
        $id = (string) $this->meta_pixel_id;

        return preg_match(self::META_PIXEL_ID_PATTERN, $id) ? $id : null;
    }
}
