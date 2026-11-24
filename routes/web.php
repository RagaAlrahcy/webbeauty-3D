<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Public site. Indonesian (default) has no URL prefix, English lives under /en.
| Route names: `home`, `products.index`, `products.show` and the same names
| prefixed with `en.` for English. Use localized_route() to build links.
*/

Route::middleware('locale:id')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produk/{slug}', [ProductController::class, 'show'])->name('products.show');
});

Route::middleware('locale:en')->prefix('en')->name('en.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
