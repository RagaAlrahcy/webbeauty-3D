<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog;
use App\Support\Locales;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    public function __invoke(Catalog $catalog): Response
    {
        $sitemap = Sitemap::create();

        foreach (['home', 'products.index'] as $route) {
            $sitemap->add($this->localizedUrl($route));
        }

        $catalog->products()->each(function (Product $product) use ($sitemap): void {
            $sitemap->add($this->localizedUrl('products.show', ['slug' => $product->slug])
                ->setLastModificationDate($product->updated_at));
        });

        return $sitemap->toResponse(request());
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function localizedUrl(string $route, array $parameters = []): Url
    {
        $url = Url::create(Locales::route($route, $parameters, Locales::DEFAULT));

        foreach (array_keys(Locales::SUPPORTED) as $locale) {
            $url->addAlternate(Locales::route($route, $parameters, $locale), $locale);
        }

        return $url;
    }
}
