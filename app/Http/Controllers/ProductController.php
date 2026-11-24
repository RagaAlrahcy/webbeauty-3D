<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, Catalog $catalog): View
    {
        $category = $request->string('category')->trim()->toString() ?: null;
        $categories = $catalog->categories();

        if ($category !== null && ! $categories->contains('slug', $category)) {
            $category = null;
        }

        return view('pages.products.index', [
            'products' => $catalog->products($category),
            'categories' => $categories,
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug, Catalog $catalog): View
    {
        $product = $catalog->findPublished($slug);

        abort_if($product === null, 404);

        return view('pages.products.show', [
            'product' => $product,
            'related' => $catalog->products($product->category->slug)
                ->reject(fn (Product $item): bool => $item->is($product))
                ->take(3)
                ->values(),
        ]);
    }
}
