<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PostController extends Controller
{
    private const int PER_PAGE = 9;

    public function index(Request $request, Catalog $catalog): View
    {
        $posts = $catalog->posts();
        $page = Paginator::resolveCurrentPage();

        return view('pages.posts.index', [
            'posts' => new LengthAwarePaginator(
                $posts->forPage($page, self::PER_PAGE)->values(),
                $posts->count(),
                self::PER_PAGE,
                $page,
                ['path' => $request->url()],
            ),
        ]);
    }

    public function show(string $slug, Catalog $catalog): View
    {
        $post = $catalog->findPost($slug);

        abort_if($post === null, 404);

        return view('pages.posts.show', [
            'post' => $post,
            'more' => $catalog->posts()
                ->reject(fn (Post $item): bool => $item->is($post))
                ->take(3)
                ->values(),
        ]);
    }
}
