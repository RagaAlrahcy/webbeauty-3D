<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /** Subscribing twice is not an error: the visitor sees the same confirmation. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('newsletter', [
            'email' => ['required', 'email', 'max:255'],
            'website' => ['prohibited'],
        ], [], ['email' => __('site.forms.attributes.email')]);

        Subscriber::query()->firstOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['locale' => app()->getLocale()],
        );

        return redirect()->to($this->backUrl().'#newsletter')->with('form_sent', 'newsletter');
    }

    /** Previous page on this site; never an external URL taken from the Referer header. */
    private function backUrl(): string
    {
        $previous = (string) strtok(url()->previous(), '#');
        $root = url('/');

        return $previous === $root || str_starts_with($previous, $root.'/')
            ? $previous
            : localized_route('home');
    }
}
