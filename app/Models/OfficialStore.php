<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplacePlatform;
use App\Enums\StoreType;
use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** Official online shop or offline store listed on the "where to buy" page. */
#[Fillable(['name', 'type', 'platform', 'url', 'city', 'address', 'maps_url', 'phone', 'is_published', 'sort_order'])]
#[ObservedBy(CatalogCacheObserver::class)]
class OfficialStore extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'type' => StoreType::class,
            'platform' => MarketplacePlatform::class,
            'is_published' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Max, 240, 240)
            ->format('webp')
            ->performOnCollections('logo');
    }
}
