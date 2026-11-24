<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PromotionPlacement;
use App\Observers\CatalogCacheObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/** Scheduled announcement bar text or homepage promo banner. */
#[Fillable(['placement', 'title', 'body', 'link_label', 'link_url', 'starts_at', 'ends_at', 'is_active', 'sort_order'])]
#[ObservedBy(CatalogCacheObserver::class)]
class Promotion extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    /** @var list<string> */
    public array $translatable = ['title', 'body', 'link_label'];

    protected function casts(): array
    {
        return [
            'placement' => PromotionPlacement::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Active and inside its optional start/end window. */
    public function isRunning(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gte($at));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('banner')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->performOnCollections('image');
    }
}
