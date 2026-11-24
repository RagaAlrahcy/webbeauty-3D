@php
    use App\Support\Locales;

    $locale = app()->getLocale();
    $brand = $site->brand_name;
    $pageTitle = $title ? $title.' · '.$brand : $brand.($site->tagline ? ' · '.$site->tagline : '');
    $metaDescription = str($description ?? $site->tagline ?? '')->limit(160)->toString();
    $canonical = url()->current();
    $logo = $site->getFirstMedia('logo');
    $hex = '/^#[0-9a-fA-F]{6}$/';
    $brandColor = preg_match($hex, (string) $site->primary_color) ? $site->primary_color : '#a35d6a';
    $accentColor = preg_match($hex, (string) $site->accent_color) ? $site->accent_color : '#f4e1dc';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    @foreach (Locales::SUPPORTED as $code => $meta)
        <link rel="alternate" hreflang="{{ $meta['hreflang'] }}" href="{{ Locales::currentUrlFor($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ Locales::currentUrlFor(Locales::DEFAULT) }}">

    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title ?? $brand }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ Locales::SUPPORTED[$locale]['og'] }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    <meta name="theme-color" content="{{ $accentColor }}">

    @stack('head')

    <style>:root { --brand: {{ $brandColor }}; --brand-soft: {{ $accentColor }}; }</style>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-paper">
        {{ __('site.nav.skip') }}
    </a>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line/70 bg-paper/80 backdrop-blur-md">
        <div class="container-page flex h-16 items-center justify-between gap-4 sm:h-20">
            <a href="{{ localized_route('home') }}" class="flex items-center gap-3" aria-label="{{ $brand }} — {{ __('site.nav.home') }}">
                @if ($logo)
                    <img src="{{ $logo->getUrl() }}" alt="" class="h-9 w-auto" width="120" height="36">
                @endif
                <span class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $brand }}</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium md:flex" aria-label="Utama">
                <a href="{{ localized_route('home') }}" @class(['transition hover:text-brand', 'text-brand' => request()->routeIs('home', 'en.home')])>{{ __('site.nav.home') }}</a>
                <a href="{{ localized_route('products.index') }}" @class(['transition hover:text-brand', 'text-brand' => request()->routeIs('products.*', 'en.products.*')])>{{ __('site.nav.products') }}</a>
            </nav>

            <div class="flex items-center gap-2">
                <div class="flex rounded-full border border-line p-1 text-xs font-semibold" role="group" aria-label="{{ __('site.nav.language') }}">
                    @foreach (Locales::SUPPORTED as $code => $meta)
                        <a
                            href="{{ Locales::currentUrlFor($code) }}"
                            hreflang="{{ $meta['hreflang'] }}"
                            lang="{{ $code }}"
                            title="{{ $meta['label'] }}"
                            @if ($code === $locale) aria-current="true" @endif
                            @class([
                                'grid min-h-9 min-w-9 place-items-center rounded-full px-2 uppercase transition',
                                'bg-ink text-paper' => $code === $locale,
                                'text-ink-muted hover:text-ink' => $code !== $locale,
                            ])
                        >{{ $code }}</a>
                    @endforeach
                </div>

                <button
                    type="button"
                    class="grid size-11 place-items-center rounded-full border border-line md:hidden"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open"
                    aria-controls="mobile-menu"
                    x-bind:aria-label="open ? @js(__('site.nav.close_menu')) : @js(__('site.nav.menu'))"
                    aria-label="{{ __('site.nav.menu') }}"
                >
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path x-show="! open" d="M4 7h16M4 12h16M4 17h16" />
                        <path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" x-show="open" x-cloak x-transition.opacity class="border-t border-line md:hidden" aria-label="Mobile">
            <div class="container-page flex flex-col gap-1 py-4 text-base font-medium">
                <a href="{{ localized_route('home') }}" class="rounded-xl px-3 py-3 hover:bg-brand-soft">{{ __('site.nav.home') }}</a>
                <a href="{{ localized_route('products.index') }}" class="rounded-xl px-3 py-3 hover:bg-brand-soft">{{ __('site.nav.products') }}</a>
            </div>
        </nav>
    </header>

    <main id="content">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-line bg-paper-raised">
        <div class="container-page grid gap-10 py-14 md:grid-cols-3">
            <div>
                <p class="font-display text-3xl font-semibold">{{ $brand }}</p>
                @if ($site->tagline)
                    <p class="mt-3 max-w-xs text-sm text-ink-muted">{{ $site->tagline }}</p>
                @endif
            </div>

            <div class="text-sm">
                <p class="eyebrow">{{ __('site.footer.contact') }}</p>
                <ul class="mt-4 space-y-2 text-ink-muted">
                    @if ($site->email)<li><a class="hover:text-brand" href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>@endif
                    @if ($site->phone)<li><a class="hover:text-brand" href="tel:{{ preg_replace('/[^\d+]/', '', $site->phone) }}">{{ $site->phone }}</a></li>@endif
                    @if ($site->address)<li class="whitespace-pre-line">{{ $site->address }}</li>@endif
                </ul>
            </div>

            <div class="text-sm">
                <p class="eyebrow">{{ __('site.footer.follow') }}</p>
                <ul class="mt-4 flex flex-wrap gap-3">
                    @foreach (['Instagram' => $site->instagram_url, 'TikTok' => $site->tiktok_url, 'WhatsApp' => $site->whatsappUrl(), 'Marketplace' => $site->marketplace_url] as $label => $url)
                        @if ($url)
                            <li><a href="{{ $url }}" class="btn-ghost min-h-10 px-4 py-2" rel="noopener" target="_blank">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="border-t border-line">
            <p class="container-page py-6 text-xs text-ink-muted">© {{ now()->year }} {{ $brand }}. {{ __('site.footer.rights') }}</p>
        </div>
    </footer>
</body>
</html>
