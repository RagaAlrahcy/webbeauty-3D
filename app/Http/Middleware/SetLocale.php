<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = Locales::DEFAULT): Response
    {
        app()->setLocale(Locales::isSupported($locale) ? $locale : Locales::DEFAULT);

        return $next($request);
    }
}
