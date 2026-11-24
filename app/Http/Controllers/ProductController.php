<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog;
use App\Support\ProductFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ProductController extends Controller
{
    public function index(Request $request, Catalog $catalog): View
    {
        $categories = $catalog->categories();
        $filters = ProductFilters::fromRequest($request, $categories->pluck('slug'));

        $allProducts = $catalog->products();
        $results = $filters->apply($allProducts);
        $page = Paginator::resolveCurrentPage();

        $products = new LengthAwarePaginator(
            $results->forPage($page, ProductFilters::PER_PAGE)->values(),
            $results->count(),
            ProductFilters::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('pages.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'facets' => ProductFilters::facets($allProducts),
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
