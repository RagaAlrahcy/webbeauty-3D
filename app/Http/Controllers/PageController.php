<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PageKey;
use App\Enums\StoreType;
use App\Services\Catalog;
use Illuminate\Contracts\View\View;

/** Content pages edited in the CMS. A page that was never created returns 404. */
class PageController extends Controller
{
    public function __construct(private readonly Catalog $catalog) {}

    public function about(): View
    {
        return $this->render(PageKey::About);
    }

    public function privacy(): View
    {
        return $this->render(PageKey::Privacy);
    }

    public function terms(): View
    {
        return $this->render(PageKey::Terms);
    }

    public function faq(): View
    {
        $faqs = $this->catalog->generalFaqs();

        abort_if($faqs->isEmpty(), 404);

        return view('pages.faq', ['faqs' => $faqs]);
    }

    public function whereToBuy(): View
    {
        $stores = $this->catalog->officialStores();

        return view('pages.where-to-buy', [
            'marketplaces' => $stores->where('type', StoreType::Marketplace)->values(),
            'offlineStores' => $stores->where('type', StoreType::Offline)->values(),
        ]);
    }

    private function render(PageKey $key): View
    {
        $page = $this->catalog->page($key);

        abort_if($page === null, 404);

        return view('pages.page', ['page' => $page]);
    }
}
