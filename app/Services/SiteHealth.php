<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageKey;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\OfficialStores\OfficialStoreResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Checklist of missing or soon-expiring content for the admin dashboard.
 * Each issue links to the admin page where it can be fixed.
 *
 * @phpstan-type HealthItem array{label: string, url: string}
 * @phpstan-type HealthIssue array{level: 'danger'|'warning'|'info', title: string, description: string, url: string, items: list<HealthItem>}
 */
class SiteHealth
{
    public const string DANGER = 'danger';

    public const string WARNING = 'warning';

    public const string INFO = 'info';

    /** Promotions ending within this many days are flagged. */
    public const int PROMOTION_ENDING_DAYS = 3;

    /** Product names listed under an issue; the rest is summarized as a count. */
    private const int MAX_ITEMS = 5;

    /**
     * @return list<HealthIssue>
     */
    public function issues(): array
    {
        $settings = SiteSetting::query()->first() ?? SiteSetting::current();
        $products = Product::query()->published()->with('variants')->orderBy('id')->get();

        $issues = array_filter([
            $this->missingWhatsapp($settings),
            $this->productsWhere(
                $products,
                fn (Product $product): bool => blank($product->bpom_number),
                self::WARNING,
                'missing_bpom',
            ),
            $this->missingPages(),
            $this->missingStores(),
            $this->endingPromotions(),
            $this->productsWhere(
                $products,
                fn (Product $product): bool => $product->skinTypeList() === [] && $product->skinConcernList() === [],
                self::INFO,
                'missing_suitability',
            ),
            $this->productsWhere(
                $products,
                fn (Product $product): bool => blank($product->getTranslation('how_to_use', 'id', false)),
                self::INFO,
                'missing_how_to_use',
            ),
            $this->productsWhere(
                $products,
                fn (Product $product): bool => $product->prices() === [],
                self::INFO,
                'missing_price',
            ),
            $this->missingTracking($settings),
        ]);

        return array_values($issues);
    }

    /**
     * @return HealthIssue|null
     */
    private function missingWhatsapp(SiteSetting $settings): ?array
    {
        if (filled($settings->whatsapp_number)) {
            return null;
        }

        return $this->issue(self::DANGER, 'missing_whatsapp', ManageSiteSettings::getUrl());
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  callable(Product): bool  $matches
     * @return HealthIssue|null
     */
    private function productsWhere(Collection $products, callable $matches, string $level, string $key): ?array
    {
        $found = $products->filter($matches)->values();

        if ($found->isEmpty()) {
            return null;
        }

        return $this->issue(
            $level,
            $key,
            ProductResource::getUrl(),
            ['count' => $found->count()],
            $found->take(self::MAX_ITEMS)
                ->map(fn (Product $product): array => [
                    'label' => $product->name,
                    'url' => ProductResource::getUrl('edit', ['record' => $product]),
                ])
                ->all(),
        );
    }

    /**
     * @return HealthIssue|null
     */
    private function missingPages(): ?array
    {
        $existing = Page::query()->pluck('key')->map(fn (PageKey $key): string => $key->value);

        $missing = collect([PageKey::About, PageKey::Privacy, PageKey::Terms])
            ->reject(fn (PageKey $key): bool => $existing->contains($key->value));

        if ($missing->isEmpty()) {
            return null;
        }

        return $this->issue(self::WARNING, 'missing_pages', PageResource::getUrl('create'), [
            'pages' => $missing->map(fn (PageKey $key): string => $key->getLabel())->implode(', '),
        ]);
    }

    /**
     * @return HealthIssue|null
     */
    private function missingStores(): ?array
    {
        if (OfficialStore::query()->where('is_published', true)->exists()) {
            return null;
        }

        return $this->issue(self::WARNING, 'missing_stores', OfficialStoreResource::getUrl('create'));
    }

    /**
     * @return HealthIssue|null
     */
    private function endingPromotions(): ?array
    {
        $ending = Promotion::query()
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(self::PROMOTION_ENDING_DAYS)])
            ->orderBy('ends_at')
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isRunning());

        if ($ending->isEmpty()) {
            return null;
        }

        return $this->issue(
            self::INFO,
            'ending_promotions',
            PromotionResource::getUrl(),
            ['count' => $ending->count(), 'days' => self::PROMOTION_ENDING_DAYS],
            $ending->map(fn (Promotion $promotion): array => [
                'label' => $promotion->title.' — '.$promotion->ends_at->diffForHumans(),
                'url' => PromotionResource::getUrl('edit', ['record' => $promotion]),
            ])->values()->all(),
        );
    }

    /**
     * @return HealthIssue|null
     */
    private function missingTracking(SiteSetting $settings): ?array
    {
        if ($settings->ga4Id() !== null || $settings->metaPixelId() !== null) {
            return null;
        }

        return $this->issue(self::INFO, 'missing_tracking', ManageSiteSettings::getUrl());
    }

    /**
     * @param  array<string, mixed>  $replace
     * @param  list<HealthItem>  $items
     * @return HealthIssue
     */
    private function issue(string $level, string $key, string $url, array $replace = [], array $items = []): array
    {
        return [
            'level' => $level,
            'title' => __("admin.dashboard.health.{$key}.title", $replace),
            'description' => __("admin.dashboard.health.{$key}.description", $replace),
            'url' => $url,
            'items' => $items,
        ];
    }
}
