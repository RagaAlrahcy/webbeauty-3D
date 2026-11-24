<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProductSort;
use App\Enums\SkinConcern;
use App\Enums\SkinType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Catalog filters from the query string (?category=&skin=&concern=&q=&sort=).
 *
 * Filtering runs on the cached list of published products, which stays small
 * for a single-brand catalog, so every filter combination reuses one cache entry.
 */
final readonly class ProductFilters
{
    public const int PER_PAGE = 12;

    public const int MAX_SEARCH_LENGTH = 100;

    public function __construct(
        public ?string $category = null,
        public ?SkinType $skinType = null,
        public ?SkinConcern $concern = null,
        public ?string $search = null,
        public ProductSort $sort = ProductSort::Recommended,
    ) {}

    /**
     * Unknown values are ignored instead of failing, so shared or stale links still work.
     *
     * @param  Collection<int, string>  $categorySlugs
     */
    public static function fromRequest(Request $request, Collection $categorySlugs): self
    {
        $category = $request->string('category')->trim()->toString();
        $search = Str::of($request->string('q')->toString())->squish()->limit(self::MAX_SEARCH_LENGTH, '')->toString();

        return new self(
            category: $categorySlugs->contains($category) ? $category : null,
            skinType: SkinType::tryFrom($request->string('skin')->toString()),
            concern: SkinConcern::tryFrom($request->string('concern')->toString()),
            search: $search !== '' ? $search : null,
            sort: ProductSort::tryFrom($request->string('sort')->toString()) ?? ProductSort::Recommended,
        );
    }

    /**
     * @param  Collection<int, Product>  $products  in the catalog's recommended order
     * @return Collection<int, Product>
     */
    public function apply(Collection $products): Collection
    {
        $terms = $this->search === null ? [] : explode(' ', mb_strtolower($this->search));

        $filtered = $products
            ->when($this->category, fn (Collection $items) => $items->filter(
                fn (Product $product): bool => $product->category->slug === $this->category,
            ))
            ->when($this->skinType, fn (Collection $items) => $items->filter(
                fn (Product $product): bool => in_array($this->skinType, $product->skinTypeList(), true),
            ))
            ->when($this->concern, fn (Collection $items) => $items->filter(
                fn (Product $product): bool => in_array($this->concern, $product->skinConcernList(), true),
            ))
            ->when($terms !== [], fn (Collection $items) => $items->filter(
                fn (Product $product): bool => self::matchesAll(self::searchText($product), $terms),
            ));

        return $this->sort($filtered)->values();
    }

    /**
     * Query-string parameters for the current filters, with optional overrides.
     * Pass null to remove a parameter, e.g. toQuery(['category' => null]).
     *
     * @param  array<string, ?string>  $overrides
     * @return array<string, string>
     */
    public function toQuery(array $overrides = []): array
    {
        $query = [
            'q' => $this->search,
            'category' => $this->category,
            'skin' => $this->skinType?->value,
            'concern' => $this->concern?->value,
            'sort' => $this->sort === ProductSort::Recommended ? null : $this->sort->value,
            ...$overrides,
        ];

        return array_filter($query, fn (?string $value): bool => $value !== null && $value !== '');
    }

    /** True when anything other than the sort order narrows the list. */
    public function isFiltered(): bool
    {
        return $this->category !== null || $this->skinType !== null || $this->concern !== null || $this->search !== null;
    }

    /**
     * Skin types and concerns that at least one product has, for the filter dropdowns.
     *
     * @param  Collection<int, Product>  $products
     * @return array{skinTypes: list<SkinType>, concerns: list<SkinConcern>}
     */
    public static function facets(Collection $products): array
    {
        $skinTypes = $products->flatMap(fn (Product $product): array => $product->skinTypeList());
        $concerns = $products->flatMap(fn (Product $product): array => $product->skinConcernList());

        return [
            'skinTypes' => array_values(array_filter(SkinType::cases(), fn (SkinType $case): bool => $skinTypes->contains($case))),
            'concerns' => array_values(array_filter(SkinConcern::cases(), fn (SkinConcern $case): bool => $concerns->contains($case))),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function sort(Collection $products): Collection
    {
        // Products without a price go last in both price orders.
        $byPrice = fn (bool $descending) => $products->sortBy([
            fn (Product $a, Product $b): int => ($a->lowestPrice() === null) <=> ($b->lowestPrice() === null),
            fn (Product $a, Product $b): int => $descending
                ? $b->lowestPrice() <=> $a->lowestPrice()
                : $a->lowestPrice() <=> $b->lowestPrice(),
        ]);

        return match ($this->sort) {
            ProductSort::Recommended => $products,
            ProductSort::Newest => $products->sortByDesc(fn (Product $product) => $product->published_at),
            ProductSort::PriceLow => $byPrice(false),
            ProductSort::PriceHigh => $byPrice(true),
        };
    }

    /** Name, descriptions and ingredients in every language, lowercased. */
    private static function searchText(Product $product): string
    {
        $parts = [
            ...array_values($product->getTranslations('name')),
            ...array_values($product->getTranslations('short_description')),
            ...array_values($product->category->getTranslations('name')),
            ...array_column($product->keyIngredientList(), 'name'),
        ];

        foreach ($product->getTranslations('ingredients') as $list) {
            array_push($parts, ...array_values((array) $list));
        }

        return mb_strtolower(implode(' ', array_filter($parts, 'is_string')));
    }

    /**
     * @param  list<string>  $terms
     */
    private static function matchesAll(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if (! str_contains($text, $term)) {
                return false;
            }
        }

        return true;
    }
}
