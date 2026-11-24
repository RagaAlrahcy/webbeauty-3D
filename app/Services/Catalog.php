<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Homepage;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cached read access to the public catalog.
 *
 * Cache keys contain a version number that is bumped on every content change,
 * which works with cache stores that do not support tags (file, database).
 */
class Catalog
{
    private const string VERSION_KEY = 'catalog:version';

    private const int TTL_SECONDS = 3600;

    /** Relations needed to render a product card or page without N+1 queries. */
    public const array PRODUCT_RELATIONS = ['category', 'media', 'variants.media'];

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->remember('categories', fn () => Category::query()
            ->whereHas('products', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(?string $categorySlug = null): Collection
    {
        return $this->remember('products:'.($categorySlug ?? 'all'), fn () => Product::query()
            ->published()
            ->with(self::PRODUCT_RELATIONS)
            ->when($categorySlug, fn (Builder $query) => $query->whereRelation('category', 'slug', $categorySlug))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->get());
    }

    /**
     * @return Collection<int, Product>
     */
    public function featured(int $limit = 6): Collection
    {
        return $this->remember('featured:'.$limit, fn () => Product::query()
            ->published()
            ->where('is_featured', true)
            ->with(self::PRODUCT_RELATIONS)
            ->latest('published_at')
            ->limit($limit)
            ->get());
    }

    public function homepage(): Homepage
    {
        return $this->remember('homepage', function (): Homepage {
            $homepage = Homepage::current();
            $homepage->load(['heroProduct' => fn ($query) => $query->published()->with(self::PRODUCT_RELATIONS)]);

            return $homepage;
        });
    }

    public function findPublished(string $slug): ?Product
    {
        return $this->remember('product:'.$slug, fn () => Product::query()
            ->published()
            ->with(self::PRODUCT_RELATIONS)
            ->where('slug', $slug)
            ->first());
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function remember(string $key, callable $callback): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return Cache::remember("catalog:v{$version}:{$key}", self::TTL_SECONDS, $callback);
    }
}
