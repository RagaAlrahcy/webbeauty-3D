@php
    $headline = $homepage->headline ?: ($hero?->name ?? $site->brand_name);
    $ctaUrl = $homepage->cta_url ?: localized_route('products.index');

    // Indonesian paths entered in the CMS (e.g. /produk) map to the English route on /en.
    if (app()->getLocale() === 'en' && str_starts_with($ctaUrl, '/') && ! str_starts_with($ctaUrl, '/en')) {
        $ctaUrl = $ctaUrl === '/produk' ? localized_route('products.index') : url('/en'.$ctaUrl);
    }

    $heroConfig = $hero?->viewerConfig();
@endphp

<x-app-layout :description="$homepage->subheadline ?: $site->tagline" :image="$hero?->ogImageUrl()">
    @if ($heroConfig && $heroConfig['renderMode'] === 'model3d' && $heroConfig['modelUrl'])
        @push('head')
            <link rel="preload" href="{{ $heroConfig['modelUrl'] }}" as="fetch" crossorigin>
        @endpush
    @endif

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 -top-40 -z-10 h-[38rem] bg-[radial-gradient(60%_60%_at_70%_30%,var(--brand-soft),transparent_70%)]"></div>

        <div class="container-page grid items-center gap-10 py-12 sm:py-16 lg:grid-cols-[1.05fr_1fr] lg:gap-16 lg:py-24">
            <div class="max-w-xl">
                @if ($site->tagline)
                    <p class="eyebrow">{{ $site->tagline }}</p>
                @endif
                <h1 class="heading-display mt-5 text-5xl leading-[1.02] sm:text-6xl lg:text-7xl">{{ $headline }}</h1>
                @if ($homepage->subheadline)
                    <p class="mt-6 text-lg leading-relaxed text-ink-muted">{{ $homepage->subheadline }}</p>
                @endif
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ $ctaUrl }}" class="btn-primary">
                        {{ $homepage->cta_label ?: __('site.home.explore') }}
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                    @if ($hero)
                        <a href="{{ localized_route('products.show', ['slug' => $hero->slug]) }}" class="btn-ghost">{{ $hero->name }}</a>
                    @endif
                </div>
            </div>

            @if ($hero)
                <x-product-viewer :product="$hero" :priority="true" class="aspect-square w-full lg:aspect-[4/5]" />
            @endif
        </div>
    </section>

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="container-page mt-6" aria-labelledby="categories-title">
            <h2 id="categories-title" class="sr-only">{{ __('site.home.categories') }}</h2>
            <ul class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ localized_route('products.index', ['category' => $category->slug]) }}" class="btn-ghost">{{ $category->name }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Featured --}}
    @if ($featured->isNotEmpty())
        <section class="container-page mt-20 sm:mt-28" aria-labelledby="featured-title">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="eyebrow">{{ $site->brand_name }}</p>
                    <h2 id="featured-title" class="heading-display mt-3 text-4xl sm:text-5xl">{{ __('site.home.featured') }}</h2>
                    <p class="mt-3 text-ink-muted">{{ __('site.home.featured_intro') }}</p>
                </div>
                <a href="{{ localized_route('products.index') }}" class="text-sm font-semibold text-brand underline-offset-4 hover:underline">{{ __('site.home.all_products') }} →</a>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
