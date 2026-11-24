<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PageKey;
use App\Models\Page;
use App\Models\Post;
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

        $routes = ['home', 'products.index', 'posts.index', 'where-to-buy', 'contact', 'reseller'];

        if ($catalog->generalFaqs()->isNotEmpty()) {
            $routes[] = 'faq';
        }

        foreach ($routes as $route) {
            $sitemap->add($this->localizedUrl($route));
        }

        // Only pages created in the CMS exist; the reseller page works without one.
        $catalog->pages()
            ->reject(fn (Page $page): bool => $page->key === PageKey::Reseller)
            ->each(function (Page $page) use ($sitemap): void {
                $sitemap->add($this->localizedUrl($page->key->routeName())->setLastModificationDate($page->updated_at));
            });

        $catalog->products()->each(function (Product $product) use ($sitemap): void {
            $sitemap->add($this->localizedUrl('products.show', ['slug' => $product->slug])
                ->setLastModificationDate($product->updated_at));
        });

        $catalog->posts()->each(function (Post $post) use ($sitemap): void {
            $sitemap->add($this->localizedUrl('posts.show', ['slug' => $post->slug])
                ->setLastModificationDate($post->updated_at));
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
