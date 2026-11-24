<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PageKey;
use App\Http\Requests\ResellerApplicationRequest;
use App\Models\ResellerApplication;
use App\Services\Catalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ResellerController extends Controller
{
    /** Program description comes from the "reseller" page in the CMS (optional). */
    public function show(Catalog $catalog): View
    {
        return view('pages.reseller', ['page' => $catalog->page(PageKey::Reseller)]);
    }

    public function store(ResellerApplicationRequest $request): RedirectResponse
    {
        ResellerApplication::query()->create([
            ...$request->safe()->only(['name', 'phone', 'email', 'city', 'business_type', 'shop_url', 'message']),
            'locale' => app()->getLocale(),
        ]);

        return redirect(localized_route('reseller'))->with('form_sent', 'reseller');
    }
}
