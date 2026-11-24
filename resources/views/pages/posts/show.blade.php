@php
    $cover = $post->getFirstMedia('cover');
    $content = $post->safeContent();

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'description' => $post->metaDescription(),
        'image' => array_values(array_filter([$post->coverUrl('full')])),
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $site->brand_name],
        'publisher' => ['@type' => 'Organization', 'name' => $site->brand_name],
        'mainEntityOfPage' => url()->current(),
    ]);
@endphp

<x-app-layout :title="$post->metaTitle()" :description="$post->metaDescription()" :image="$post->coverUrl('og')" type="article">
    @push('head')
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

    <article class="container-page pt-8 sm:pt-12">
        <a href="{{ localized_route('posts.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm text-ink-muted hover:text-brand">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
            {{ __('site.posts.back') }}
        </a>

        <header class="mt-6 max-w-3xl">
            <p class="text-sm text-ink-muted">
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('j F Y') }}</time>
                · {{ __('site.posts.minutes', ['count' => $post->readingMinutes()]) }}
            </p>
            <h1 class="heading-display mt-4 text-5xl leading-[1.05] sm:text-6xl">{{ $post->title }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $post->excerpt }}</p>
        </header>

        @if ($cover)
            <img src="{{ media_url($cover, 'full') }}"
                 srcset="{{ media_url($cover, 'card') }} 800w, {{ media_url($cover, 'full') }} 1600w"
                 sizes="(min-width: 1280px) 1216px, 100vw"
                 alt="" class="mt-10 aspect-[16/8] w-full rounded-[var(--radius-card)] object-cover"
                 width="1600" height="800" fetchpriority="high" decoding="async">
        @endif

        @if ($content)
            <div class="prose-soft mt-12 max-w-3xl text-lg">{!! $content !!}</div>
        @endif
    </article>

    @if ($post->products->isNotEmpty())
        <section class="container-page mt-20" aria-labelledby="post-products-title">
            <h2 id="post-products-title" class="heading-display text-4xl">{{ __('site.posts.products') }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($post->products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($more->isNotEmpty())
        <section class="container-page mt-20" aria-labelledby="more-posts-title">
            <h2 id="more-posts-title" class="heading-display text-4xl">{{ __('site.posts.more') }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($more as $item)
                    <x-post-card :post="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
