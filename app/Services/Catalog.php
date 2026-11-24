<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageKey;
use App\Enums\PromotionPlacement;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Homepage;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** Extra relations shown only on the product detail page. */
    private const array PRODUCT_PAGE_RELATIONS = ['faqs', 'testimonials.media'];

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
            ->with([...self::PRODUCT_RELATIONS, ...self::PRODUCT_PAGE_RELATIONS])
            ->where('slug', $slug)
            ->first());
    }

    /**
     * Testimonials marked for the homepage.
     *
     * @return Collection<int, Testimonial>
     */
    public function featuredTestimonials(int $limit = 6): Collection
    {
        return $this->remember('testimonials:featured:'.$limit, fn () => Testimonial::query()
            ->published()
            ->where('is_featured', true)
            ->with('media')
            ->limit($limit)
            ->get());
    }

    /**
     * General FAQs (not tied to a product).
     *
     * @return Collection<int, Faq>
     */
    public function generalFaqs(): Collection
    {
        return $this->remember('faqs:general', fn () => Faq::query()
            ->whereNull('product_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * Existing content pages keyed by their PageKey value.
     *
     * @return Collection<string, Page>
     */
    public function pages(): Collection
    {
        return $this->remember('pages', fn () => Page::query()
            ->get()
            ->keyBy(fn (Page $page): string => $page->key->value));
    }

    public function page(PageKey $key): ?Page
    {
        return $this->pages()->get($key->value);
    }

    /**
     * @return Collection<int, OfficialStore>
     */
    public function officialStores(): Collection
    {
        return $this->remember('stores', fn () => OfficialStore::query()
            ->where('is_published', true)
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get());
    }

    /**
     * @return Collection<int, Post>
     */
    public function posts(): Collection
    {
        return $this->remember('posts', fn () => Post::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->get());
    }

    public function findPost(string $slug): ?Post
    {
        return $this->remember('post:'.$slug, fn () => Post::query()
            ->published()
            ->with(['media', 'products' => fn (BelongsToMany $query) => $query->published()->with(self::PRODUCT_RELATIONS)])
            ->where('slug', $slug)
            ->first());
    }

    /**
     * Promotions running right now for a placement. The active list is cached;
     * the start/end window is checked on every request so schedules stay exact.
     *
     * @return Collection<int, Promotion>
     */
    public function runningPromotions(PromotionPlacement $placement): Collection
    {
        $promotions = $this->remember('promotions', fn () => Promotion::query()
            ->where('is_active', true)
            ->with('media')
            ->orderBy('sort_order')
            ->get());

        return $promotions
            ->filter(fn (Promotion $promotion): bool => $promotion->placement === $placement && $promotion->isRunning())
            ->values();
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
