<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(ContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::query()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'locale' => app()->getLocale(),
        ]);

        return redirect(localized_route('contact'))->with('form_sent', 'contact');
    }
}
