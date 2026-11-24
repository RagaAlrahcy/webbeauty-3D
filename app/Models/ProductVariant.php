<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[Fillable(['product_id', 'label', 'color', 'sort_order'])]
#[ObservedBy(CatalogCacheObserver::class)]
class ProductVariant extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    /** @var list<string> */
    public array $translatable = ['label'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('label_texture')->singleFile();
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('texture')
            ->fit(Fit::Max, 2048, 2048)
            ->format('webp')
            ->performOnCollections('label_texture');

        $this->addMediaConversion('card')
            ->fit(Fit::Max, 800, 800)
            ->format('webp')
            ->performOnCollections('image');
    }

    /**
     * @return array{label: string, color: string, labelTextureUrl: ?string, imageUrl: ?string}
     */
    public function viewerConfig(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'color' => $this->color,
            'labelTextureUrl' => media_url($this->getFirstMedia('label_texture'), 'texture'),
            'imageUrl' => media_url($this->getFirstMedia('image'), 'card'),
        ];
    }
}
