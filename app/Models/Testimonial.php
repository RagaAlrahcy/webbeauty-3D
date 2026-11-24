<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/** Customer testimonial curated by the admin, optionally with before/after photos. */
#[Fillable(['product_id', 'customer_name', 'customer_info', 'content', 'rating', 'is_featured', 'is_published', 'sort_order'])]
#[ObservedBy(CatalogCacheObserver::class)]
class Testimonial extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    /** @var list<string> */
    public array $translatable = ['customer_info', 'content'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort_order')->latest('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
        $this->addMediaCollection('before')->singleFile();
        $this->addMediaCollection('after')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 160, 160)
            ->format('webp')
            ->performOnCollections('photo');

        $this->addMediaConversion('card')
            ->fit(Fit::Max, 800, 800)
            ->format('webp')
            ->performOnCollections('before', 'after');
    }

    public function hasBeforeAfter(): bool
    {
        return $this->hasMedia('before') && $this->hasMedia('after');
    }
}
