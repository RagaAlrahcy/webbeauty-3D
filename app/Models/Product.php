<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnimationPreset;
use App\Enums\ContainerShape;
use App\Enums\Material;
use App\Enums\ProductClaim;
use App\Enums\ProductStatus;
use App\Enums\RenderMode;
use App\Enums\SkinConcern;
use App\Enums\SkinType;
use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'category_id', 'name', 'slug', 'short_description', 'description', 'price', 'ingredients',
    'bpom_number', 'halal_number', 'net_content', 'skin_types', 'skin_concerns', 'claims',
    'key_ingredients', 'how_to_use', 'warnings', 'marketplace_url',
    'render_mode', 'container_shape', 'base_color', 'material', 'animation_preset',
    'background_theme', 'camera_position', 'is_featured', 'status', 'published_at',
    'meta_title', 'meta_description',
])]
#[ObservedBy(CatalogCacheObserver::class)]
class Product extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    public const string DEFAULT_COLOR = '#f2d4cf';

    /** Public registration lookups, linked next to the BPOM and halal numbers. */
    public const string BPOM_CHECK_URL = 'https://cekbpom.pom.go.id/';

    public const string HALAL_CHECK_URL = 'https://bpjph.halal.go.id/';

    /** @var list<string> */
    public array $translatable = [
        'name', 'short_description', 'description', 'ingredients', 'how_to_use', 'warnings',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'render_mode' => RenderMode::class,
            'container_shape' => ContainerShape::class,
            'material' => Material::class,
            'animation_preset' => AnimationPreset::class,
            'status' => ProductStatus::class,
            'camera_position' => 'array',
            'skin_types' => 'array',
            'skin_concerns' => 'array',
            'claims' => 'array',
            'key_ingredients' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->where('is_published', true)->orderBy('sort_order');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)->where('is_published', true)->orderBy('sort_order');
    }

    /**
     * @return list<SkinType>
     */
    public function skinTypeList(): array
    {
        return self::enumList($this->skin_types, SkinType::class);
    }

    /**
     * @return list<SkinConcern>
     */
    public function skinConcernList(): array
    {
        return self::enumList($this->skin_concerns, SkinConcern::class);
    }

    /**
     * @return list<ProductClaim>
     */
    public function claimList(): array
    {
        return self::enumList($this->claims, ProductClaim::class);
    }

    /**
     * Key ingredients with the benefit text in the current locale.
     *
     * @return list<array{name: string, concentration: ?string, benefit: ?string}>
     */
    public function keyIngredientList(): array
    {
        $locale = app()->getLocale();

        return collect((array) $this->key_ingredients)
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['name'] ?? null))
            ->map(function (array $item) use ($locale): array {
                $benefit = (array) ($item['benefit'] ?? []);

                return [
                    'name' => (string) $item['name'],
                    'concentration' => filled($item['concentration'] ?? null) ? (string) $item['concentration'] : null,
                    'benefit' => ($benefit[$locale] ?? null) ?: ($benefit[config('app.fallback_locale')] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Product price plus any variant price overrides.
     *
     * @return list<int>
     */
    public function prices(): array
    {
        return $this->variants
            ->pluck('price')
            ->push($this->price)
            ->filter(fn (?int $price): bool => $price !== null)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function lowestPrice(): ?int
    {
        return $this->prices()[0] ?? null;
    }

    /** "Rp 99.000", or "Mulai Rp 99.000" when variants are priced differently. */
    public function formattedPriceRange(): ?string
    {
        $prices = $this->prices();

        if ($prices === []) {
            return null;
        }

        $lowest = self::formatRupiah($prices[0]);

        return count($prices) > 1 ? __('site.products.price_from', ['price' => $lowest]) : $lowest;
    }

    public static function formatRupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return list<T>
     */
    private static function enumList(mixed $values, string $enum): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $value) => is_string($value) ? $enum::tryFrom($value) : null,
            (array) $values,
        )));
    }

    /** Products visible on the public site. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);

        $this->addMediaCollection('model')->singleFile();

        $this->addMediaCollection('label_texture')->singleFile();

        $this->addMediaCollection('og_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (['thumb' => 400, 'card' => 800, 'full' => 1600] as $name => $size) {
            $this->addMediaConversion($name)
                ->fit(Fit::Max, $size, $size)
                ->format('webp')
                ->performOnCollections('gallery');
        }

        $this->addMediaConversion('texture')
            ->fit(Fit::Max, 2048, 2048)
            ->format('webp')
            ->performOnCollections('label_texture');

        $this->addMediaConversion('og')
            ->fit(Fit::Crop, 1200, 630)
            ->format('jpg')
            ->performOnCollections('og_image', 'gallery');
    }

    public function primaryImage(): ?Media
    {
        return $this->getFirstMedia('gallery');
    }

    public function imageUrl(string $conversion = 'card'): ?string
    {
        return media_url($this->primaryImage(), $conversion);
    }

    public function ogImageUrl(): ?string
    {
        return media_url($this->getFirstMedia('og_image') ?? $this->primaryImage(), 'og');
    }

    /** Render mode actually usable, e.g. model3d without an uploaded GLB falls back. */
    public function effectiveRenderMode(): RenderMode
    {
        $mode = $this->render_mode ?? RenderMode::Image;

        if ($mode === RenderMode::Model3d && ! $this->hasMedia('model')) {
            return $this->container_shape ? RenderMode::Procedural : RenderMode::Image;
        }

        if ($mode === RenderMode::Procedural && $this->container_shape === null) {
            return RenderMode::Image;
        }

        return $mode;
    }

    /**
     * Data needed by the Three.js viewer only. Keep it free of anything sensitive.
     *
     * @return array<string, mixed>
     */
    public function viewerConfig(): array
    {
        $mode = $this->effectiveRenderMode();
        $preset = $this->animation_preset ?? AnimationPreset::None;

        if ($preset->requiresModel() && $mode !== RenderMode::Model3d) {
            $preset = AnimationPreset::Rotate;
        }

        $camera = $this->camera_position;

        return [
            'renderMode' => $mode->value,
            'modelUrl' => $mode === RenderMode::Model3d ? $this->getFirstMediaUrl('model') : null,
            'labelTextureUrl' => media_url($this->getFirstMedia('label_texture'), 'texture'),
            'labelText' => $this->name,
            'containerShape' => $this->container_shape?->value,
            'baseColor' => $this->base_color ?: self::DEFAULT_COLOR,
            'material' => ($this->material ?? Material::Glossy)->value,
            'animationPreset' => $preset->value,
            'backgroundTheme' => $this->background_theme,
            'camera' => is_array($camera) && isset($camera['x'], $camera['y'], $camera['z'])
                ? ['x' => (float) $camera['x'], 'y' => (float) $camera['y'], 'z' => (float) $camera['z']]
                : null,
            'variants' => $this->variants
                ->map(fn (ProductVariant $variant): array => $variant->viewerConfig())
                ->values()
                ->all(),
        ];
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function metaDescription(): string
    {
        return $this->meta_description ?: $this->short_description;
    }

    public function formattedPrice(): ?string
    {
        return $this->price === null ? null : self::formatRupiah($this->price);
    }
}
