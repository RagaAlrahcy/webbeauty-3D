@php
    use App\Enums\PageKey;
    use App\Support\Locales;

    $locale = app()->getLocale();
    $brand = $site->brand_name;
    $pageTitle = $title ? $title.' · '.$brand : $brand.($site->tagline ? ' · '.$site->tagline : '');
    $metaDescription = str($description ?? $site->tagline ?? '')->limit(160)->toString();
    $canonical = url()->current();
    $logo = $site->getFirstMedia('logo');
    $favicon = $site->faviconUrl();
    $hex = '/^#[0-9a-fA-F]{6}$/';
    $brandColor = preg_match($hex, (string) $site->primary_color) ? $site->primary_color : '#a35d6a';
    $accentColor = preg_match($hex, (string) $site->accent_color) ? $site->accent_color : '#f4e1dc';
    $ga4Id = $site->ga4Id();
    $metaPixelId = $site->metaPixelId();

    $hasPage = fn (PageKey $key): bool => $contentPages->has($key->value);

    // [route, label, active pattern]; links to pages that were never created are hidden.
    $navigation = array_filter([
        ['home', __('site.nav.home'), 'home'],
        ['products.index', __('site.nav.products'), 'products.*'],
        ['posts.index', __('site.nav.articles'), 'posts.*'],
        $hasPage(PageKey::About) ? ['about', __('site.nav.about'), 'about'] : null,
        ['where-to-buy', __('site.nav.where_to_buy'), 'where-to-buy'],
        ['contact', __('site.nav.contact'), 'contact'],
    ]);

    $helpLinks = array_filter([
        $hasFaq ? ['faq', __('site.nav.faq')] : null,
        ['contact', __('site.nav.contact')],
        ['where-to-buy', __('site.nav.where_to_buy')],
        ['reseller', __('site.nav.reseller')],
    ]);

    $legalLinks = array_filter([
        $hasPage(PageKey::Privacy) ? ['privacy', PageKey::Privacy->getLabel()] : null,
        $hasPage(PageKey::Terms) ? ['terms', PageKey::Terms->getLabel()] : null,
    ]);

    $isActive = fn (string $pattern): bool => request()->routeIs($pattern, 'en.'.$pattern);
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

    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @endif

    @stack('head')

    <style>:root { --brand: {{ $brandColor }}; --brand-soft: {{ $accentColor }}; }</style>

    {{-- IDs are validated against a strict pattern (SiteSetting::ga4Id/metaPixelId) before printing. --}}
    @if ($ga4Id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ $ga4Id }}');
        </script>
    @endif
    @if ($metaPixelId)
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $metaPixelId }}');
            fbq('track', 'PageView');
        </script>
    @endif

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-paper">
        {{ __('site.nav.skip') }}
    </a>

    @if ($announcements->isNotEmpty())
        <div class="bg-ink text-paper" role="region" aria-label="{{ __('site.announcement') }}">
            <div class="container-page flex flex-col items-center gap-1 py-2.5 text-center text-xs sm:text-sm">
                @foreach ($announcements as $announcement)
                    <p>
                        <span class="font-semibold">{{ $announcement->title }}</span>
                        @if ($announcement->body)
                            <span class="text-paper/80">{{ $announcement->body }}</span>
                        @endif
                        @if ($announcement->link_url)
                            <a href="{{ $announcement->link_url }}" class="ml-1 font-semibold underline underline-offset-4 hover:text-brand-soft">{{ $announcement->link_label ?: __('site.posts.read_more') }}</a>
                        @endif
                    </p>
                @endforeach
            </div>
        </div>
    @endif

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line/70 bg-paper/80 backdrop-blur-md">
        <div class="container-page flex h-16 items-center justify-between gap-4 sm:h-20">
            <a href="{{ localized_route('home') }}" class="flex items-center gap-3" aria-label="{{ $brand }} — {{ __('site.nav.home') }}">
                @if ($logo)
                    <img src="{{ $logo->getUrl() }}" alt="" class="h-9 w-auto" width="120" height="36">
                @endif
                <span class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $brand }}</span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium lg:flex" aria-label="{{ __('site.nav.main') }}">
                @foreach ($navigation as [$route, $label, $pattern])
                    <a href="{{ localized_route($route) }}"
                       @if ($isActive($pattern)) aria-current="page" @endif
                       @class(['transition hover:text-brand', 'text-brand' => $isActive($pattern)])>{{ $label }}</a>
                @endforeach
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
                    class="grid size-11 place-items-center rounded-full border border-line lg:hidden"
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

        <nav id="mobile-menu" x-show="open" x-cloak x-transition.opacity class="border-t border-line lg:hidden" aria-label="Mobile">
            <div class="container-page flex flex-col gap-1 py-4 text-base font-medium">
                @foreach ($navigation as [$route, $label, $pattern])
                    <a href="{{ localized_route($route) }}"
                       @if ($isActive($pattern)) aria-current="page" @endif
                       @class(['rounded-xl px-3 py-3 hover:bg-brand-soft', 'bg-brand-soft' => $isActive($pattern)])>{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main id="content">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-line bg-paper-raised">
        <div class="container-page grid gap-12 py-14 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <p class="font-display text-3xl font-semibold">{{ $brand }}</p>
                @if ($site->tagline)
                    <p class="mt-3 max-w-xs text-sm text-ink-muted">{{ $site->tagline }}</p>
                @endif

                <x-newsletter-form class="mt-8 max-w-sm" :privacy-url="$hasPage(PageKey::Privacy) ? localized_route('privacy') : null" />
            </div>

            <div class="text-sm">
                <p class="eyebrow">{{ __('site.footer.explore') }}</p>
                <ul class="mt-4 space-y-3 text-ink-muted">
                    @foreach ($navigation as [$route, $label])
                        @continue($route === 'home' || $route === 'contact')
                        <li><a class="hover:text-brand" href="{{ localized_route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="text-sm">
                <p class="eyebrow">{{ __('site.footer.help') }}</p>
                <ul class="mt-4 space-y-3 text-ink-muted">
                    @foreach ($helpLinks as [$route, $label])
                        <li><a class="hover:text-brand" href="{{ localized_route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="text-sm">
                <p class="eyebrow">{{ __('site.footer.contact') }}</p>
                <ul class="mt-4 space-y-3 text-ink-muted">
                    @if ($site->email)<li><a class="hover:text-brand" href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>@endif
                    @if ($site->phone)<li><a class="hover:text-brand" href="tel:{{ preg_replace('/[^\d+]/', '', $site->phone) }}">{{ $site->phone }}</a></li>@endif
                    @if ($site->address)<li class="whitespace-pre-line">{{ $site->address }}</li>@endif
                </ul>

                <p class="eyebrow mt-8">{{ __('site.footer.follow') }}</p>
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach (['Instagram' => $site->instagram_url, 'TikTok' => $site->tiktok_url, 'WhatsApp' => $site->whatsappUrl()] as $label => $url)
                        @if ($url)
                            <li>
                                <a href="{{ $url }}" class="btn-ghost min-h-10 px-4 py-2" rel="noopener" target="_blank"
                                   @if ($label === 'WhatsApp') data-track="whatsapp_click" data-track-label="footer" @endif>{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="border-t border-line">
            <div class="container-page flex flex-col gap-3 py-6 text-xs text-ink-muted sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ now()->year }} {{ $brand }}. {{ __('site.footer.rights') }}</p>
                @if ($legalLinks)
                    <ul class="flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($legalLinks as [$route, $label])
                            <li><a class="hover:text-brand" href="{{ localized_route($route) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </footer>
</body>
</html>
