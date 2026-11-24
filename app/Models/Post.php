<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/** Blog article (skincare tips, ingredient education) with related products. */
#[Fillable(['title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'meta_title', 'meta_description'])]
#[ObservedBy(CatalogCacheObserver::class)]
class Post extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    /** Average adult reading speed used for the "x min read" label. */
    private const int WORDS_PER_MINUTE = 200;

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Posts visible on the public site. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (['card' => 800, 'full' => 1600] as $name => $size) {
            $this->addMediaConversion($name)
                ->fit(Fit::Max, $size, $size)
                ->format('webp')
                ->performOnCollections('cover');
        }

        $this->addMediaConversion('og')
            ->fit(Fit::Crop, 1200, 630)
            ->format('jpg')
            ->performOnCollections('cover');
    }

    public function coverUrl(string $conversion = 'card'): ?string
    {
        return media_url($this->getFirstMedia('cover'), $conversion);
    }

    /** Rich-text content, sanitized for output with {!! !!}. */
    public function safeContent(): ?string
    {
        return $this->content ? Str::sanitizeHtml($this->content) : null;
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        return $this->meta_description ?: $this->excerpt;
    }
}
