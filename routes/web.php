<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Public site. Indonesian (default) has no URL prefix, English lives under /en.
| Both languages register the same route names (English prefixed with `en.`)
| from one definition; only the path segments differ. Build links with
| localized_route('products.index') etc.
*/

$publicRoutes = function (string $locale): void {
    $path = fn (string $id, string $en): string => $locale === 'en' ? $en : $id;

    Route::get('/', HomeController::class)->name('home');

    Route::get($path('/produk', '/products'), [ProductController::class, 'index'])->name('products.index');
    Route::get($path('/produk/{slug}', '/products/{slug}'), [ProductController::class, 'show'])->name('products.show');

    Route::get($path('/artikel', '/articles'), [PostController::class, 'index'])->name('posts.index');
    Route::get($path('/artikel/{slug}', '/articles/{slug}'), [PostController::class, 'show'])->name('posts.show');

    Route::get($path('/tentang-kami', '/about'), [PageController::class, 'about'])->name('about');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get($path('/beli-di-mana', '/where-to-buy'), [PageController::class, 'whereToBuy'])->name('where-to-buy');
    Route::get($path('/kebijakan-privasi', '/privacy-policy'), [PageController::class, 'privacy'])->name('privacy');
    Route::get($path('/syarat-dan-ketentuan', '/terms'), [PageController::class, 'terms'])->name('terms');

    Route::get($path('/kontak', '/contact'), [ContactController::class, 'show'])->name('contact');
    Route::get('/reseller', [ResellerController::class, 'show'])->name('reseller');

    Route::middleware('throttle:public-forms')->group(function () use ($path): void {
        Route::post($path('/kontak', '/contact'), [ContactController::class, 'store'])->name('contact.store');
        Route::post('/reseller', [ResellerController::class, 'store'])->name('reseller.store');
        Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');
    });
};

Route::middleware('locale:id')->group(fn () => $publicRoutes('id'));

Route::middleware('locale:en')->prefix('en')->name('en.')->group(fn () => $publicRoutes('en'));

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
