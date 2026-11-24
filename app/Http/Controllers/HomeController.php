<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PromotionPlacement;
use App\Services\Catalog;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(Catalog $catalog): View
    {
        $homepage = $catalog->homepage();
        $featured = $catalog->featured();

        $hero = $homepage->heroProduct ?? $featured->first();

        return view('pages.home', [
            'homepage' => $homepage,
            'hero' => $hero,
            'featured' => $featured->reject(fn ($product) => $product->is($hero))->values(),
            'categories' => $catalog->categories(),
            'promotions' => $catalog->runningPromotions(PromotionPlacement::Home),
            'testimonials' => $catalog->featuredTestimonials(),
            'posts' => $catalog->posts()->take(3),
        ]);
    }
}
