@php
    use Illuminate\Support\Str;

    $variants = $product->variants->map(fn ($variant) => $variant->viewerConfig())->values();
    $description = $product->description ? Str::sanitizeHtml($product->description) : null;
    $ingredients = array_filter((array) $product->ingredients);
    $whatsapp = $site->whatsappUrl(__('site.products.whatsapp_message', ['name' => $product->name]).' '.url()->current());

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->metaDescription(),
        'image' => array_values(array_filter([$product->imageUrl('full')])),
        'sku' => $product->slug,
        'category' => $product->category->name,
        'brand' => ['@type' => 'Brand', 'name' => $site->brand_name],
        'url' => url()->current(),
        'offers' => $product->price ? [
            '@type' => 'Offer',
            'priceCurrency' => 'IDR',
            'price' => $product->price,
            'availability' => 'https://schema.org/InStock',
            'url' => url()->current(),
        ] : null,
    ]);
@endphp

<x-app-layout
    :title="$product->metaTitle()"
    :description="$product->metaDescription()"
    :image="$product->ogImageUrl()"
    type="product"
>
    @push('head')
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

    <div class="container-page pt-8 sm:pt-12">
        <a href="{{ localized_route('products.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm text-ink-muted hover:text-brand">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
            {{ __('site.products.back') }}
        </a>
    </div>

    <article class="container-page mt-4 grid gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-16">
        <div class="lg:sticky lg:top-28 lg:self-start">
            <x-product-viewer :product="$product" :priority="true" class="aspect-square w-full" />
        </div>

        <div class="lg:py-6">
            <p class="eyebrow">{{ $product->category->name }}</p>
            <h1 class="heading-display mt-4 text-5xl leading-[1.05] sm:text-6xl">{{ $product->name }}</h1>

            @if ($product->formattedPrice())
                <p class="mt-5 text-2xl font-semibold">{{ $product->formattedPrice() }}</p>
            @endif

            <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $product->short_description }}</p>

            @if ($variants->isNotEmpty())
                <div class="mt-8" x-data="variantPicker(@js($variants))">
                    <p class="text-sm font-semibold" id="variant-label">
                        {{ __('site.products.variants') }}:
                        <span class="font-normal text-ink-muted" x-text="variants[active]?.label">{{ $variants->first()['label'] }}</span>
                    </p>
                    <div class="mt-3 flex flex-wrap gap-3" role="radiogroup" aria-labelledby="variant-label">
                        @foreach ($variants as $index => $variant)
                            <button
                                type="button"
                                role="radio"
                                x-on:click="select({{ $index }})"
                                x-bind:aria-checked="isActive({{ $index }}).toString()"
                                aria-checked="{{ $index === 0 ? 'true' : 'false' }}"
                                aria-label="{{ $variant['label'] }}"
                                title="{{ $variant['label'] }}"
                                class="grid size-11 place-items-center rounded-full border-2 border-transparent transition aria-checked:border-ink"
                            >
                                <span class="size-8 rounded-full border border-black/10 shadow-inner" style="background: {{ $variant['color'] }}"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-9 flex flex-wrap gap-3">
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" class="btn-primary" target="_blank" rel="noopener">{{ __('site.products.ask_whatsapp') }}</a>
                @endif
                @if ($site->marketplace_url)
                    <a href="{{ $site->marketplace_url }}" class="btn-ghost" target="_blank" rel="noopener">{{ __('site.products.buy_marketplace') }}</a>
                @endif
            </div>

            @if ($description)
                <section class="mt-12 border-t border-line pt-8" aria-labelledby="description-title">
                    <h2 id="description-title" class="heading-display text-3xl">{{ __('site.products.description') }}</h2>
                    <div class="prose-soft mt-4">{!! $description !!}</div>
                </section>
            @endif

            @if ($ingredients)
                <section class="mt-10 border-t border-line pt-8" aria-labelledby="ingredients-title">
                    <h2 id="ingredients-title" class="heading-display text-3xl">{{ __('site.products.ingredients') }}</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($ingredients as $ingredient)
                            <li class="rounded-full bg-brand-soft px-4 py-2 text-sm">{{ $ingredient }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="container-page mt-24" aria-labelledby="related-title">
            <h2 id="related-title" class="heading-display text-4xl">{{ __('site.products.related') }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
