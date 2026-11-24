@php
    use App\Models\Product;
    use Illuminate\Support\Str;

    $pageUrl = url()->current();
    $marketplaceUrl = $product->marketplace_url ?: $site->marketplace_url;
    $whatsapp = $site->whatsappUrl(__('site.products.whatsapp_message', ['name' => $product->name]).' '.$pageUrl);

    // Variant data for the Alpine picker; empty variant values fall back to the product's.
    $variants = $product->variants->map(function ($variant) use ($product, $site, $pageUrl, $marketplaceUrl): array {
        $data = $variant->pickerData($product);
        $data['whatsappUrl'] = $site->whatsappUrl(__('site.products.whatsapp_message_variant', ['name' => $product->name, 'variant' => $variant->label]).' '.$pageUrl);
        $data['marketplaceUrl'] = $data['marketplaceUrl'] ?: $marketplaceUrl;

        return $data;
    })->values();
    $first = $variants->first();

    $price = $first ? $first['price'] : $product->formattedPrice();
    $netContent = $first ? $first['netContent'] : $product->net_content;
    $bpomNumber = $first ? $first['bpomNumber'] : $product->bpom_number;
    $hasVariants = $variants->isNotEmpty();

    $description = $product->description ? Str::sanitizeHtml($product->description) : null;
    $ingredients = array_filter((array) $product->ingredients);
    $keyIngredients = $product->keyIngredientList();
    $skinTypes = $product->skinTypeList();
    $concerns = $product->skinConcernList();
    $claims = $product->claimList();
    $prices = $product->prices();

    $offers = match (true) {
        count($prices) > 1 => [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'IDR',
            'lowPrice' => $prices[0],
            'highPrice' => end($prices),
            'offerCount' => count($prices),
            'availability' => 'https://schema.org/InStock',
        ],
        count($prices) === 1 => [
            '@type' => 'Offer',
            'priceCurrency' => 'IDR',
            'price' => $prices[0],
            'availability' => 'https://schema.org/InStock',
            'url' => $pageUrl,
        ],
        default => null,
    };

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->metaDescription(),
        'image' => array_values(array_filter([$product->imageUrl('full')])),
        'sku' => $product->slug,
        'category' => $product->category->name,
        'brand' => ['@type' => 'Brand', 'name' => $site->brand_name],
        'url' => $pageUrl,
        'offers' => $offers,
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

        <div class="lg:py-6" x-data="variantPicker(@js($variants))">
            <p class="eyebrow">{{ $product->category->name }}</p>
            <h1 class="heading-display mt-4 text-5xl leading-[1.05] sm:text-6xl">{{ $product->name }}</h1>

            {{-- Trust badges: registration numbers link to the official lookup sites. --}}
            @if ($bpomNumber || $product->halal_number || $claims)
                <ul class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('site.products.details') }}">
                    @if ($bpomNumber)
                        <li>
                            <a href="{{ Product::BPOM_CHECK_URL }}" target="_blank" rel="noopener"
                               class="chip border border-line bg-paper-raised hover:border-brand"
                               title="{{ __('site.products.verify_bpom', ['number' => $bpomNumber]) }}">
                                <svg viewBox="0 0 24 24" class="size-4 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z" /><path d="M9 12l2 2 4-4" /></svg>
                                <span class="font-semibold">{{ __('site.products.bpom') }}</span>
                                <span class="text-ink-muted" @if ($hasVariants) x-text="current?.bpomNumber ?? @js($bpomNumber)" @endif>{{ $bpomNumber }}</span>
                                <span class="sr-only">— {{ __('site.products.verify') }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($product->halal_number)
                        <li>
                            <a href="{{ Product::HALAL_CHECK_URL }}" target="_blank" rel="noopener"
                               class="chip border border-line bg-paper-raised hover:border-brand"
                               title="{{ __('site.products.verify_halal', ['number' => $product->halal_number]) }}">
                                <svg viewBox="0 0 24 24" class="size-4 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M8.5 12.5l2.5 2.5 4.5-5" /></svg>
                                <span class="font-semibold">{{ __('site.products.halal_certified') }}</span>
                                <span class="sr-only">{{ $product->halal_number }} — {{ __('site.products.verify') }}</span>
                            </a>
                        </li>
                    @endif
                    @foreach ($claims as $claim)
                        <li class="chip bg-brand-soft">{{ $claim->getLabel() }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($price)
                <p class="mt-6 flex items-baseline gap-3">
                    <span class="text-2xl font-semibold" @if ($hasVariants) x-text="current?.price ?? @js($price)" @endif>{{ $price }}</span>
                    @if ($netContent || $hasVariants)
                        <span class="text-sm text-ink-muted" @if ($hasVariants) x-text="current?.netContent ?? ''" @endif>{{ $netContent }}</span>
                    @endif
                </p>
            @elseif ($netContent)
                <p class="mt-6 text-sm text-ink-muted">{{ __('site.products.net_content') }}: {{ $netContent }}</p>
            @endif

            <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $product->short_description }}</p>

            @if ($skinTypes || $concerns)
                <dl class="mt-7 grid gap-4 text-sm sm:grid-cols-2">
                    @if ($skinTypes)
                        <div>
                            <dt class="font-semibold">{{ __('site.products.suitable_for') }}</dt>
                            <dd class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($skinTypes as $skinType)
                                    <a href="{{ localized_route('products.index', ['skin' => $skinType->value]) }}" class="chip border border-line hover:border-brand">{{ $skinType->getLabel() }}</a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    @if ($concerns)
                        <div>
                            <dt class="font-semibold">{{ __('site.products.targets') }}</dt>
                            <dd class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($concerns as $concern)
                                    <a href="{{ localized_route('products.index', ['concern' => $concern->value]) }}" class="chip border border-line hover:border-brand">{{ $concern->getLabel() }}</a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            @endif

            @if ($hasVariants)
                <div class="mt-8">
                    <p class="text-sm font-semibold" id="variant-label">
                        {{ __('site.products.variants') }}:
                        <span class="font-normal text-ink-muted" x-text="current?.label">{{ $first['label'] }}</span>
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
                    <a href="{{ $first['whatsappUrl'] ?? $whatsapp }}" class="btn-primary" target="_blank" rel="noopener"
                       data-track="whatsapp_click" data-track-label="{{ $product->name }}"
                       @if ($hasVariants) x-bind:href="current?.whatsappUrl ?? @js($whatsapp)" @endif>
                        <svg viewBox="0 0 24 24" class="size-4" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.7 11.7 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.4-.2z" /></svg>
                        {{ __('site.products.ask_whatsapp') }}
                    </a>
                @endif
                @if ($marketplaceUrl)
                    <a href="{{ $first['marketplaceUrl'] ?? $marketplaceUrl }}" class="btn-ghost" target="_blank" rel="noopener"
                       data-track="marketplace_click" data-track-label="{{ $product->name }}"
                       @if ($hasVariants) x-bind:href="current?.marketplaceUrl ?? @js($marketplaceUrl)" @endif>
                        {{ __('site.products.buy_marketplace') }}
                    </a>
                @endif
            </div>

            @if ($description)
                <section class="mt-12 border-t border-line pt-8" aria-labelledby="description-title">
                    <h2 id="description-title" class="heading-display text-3xl">{{ __('site.products.description') }}</h2>
                    <div class="prose-soft mt-4">{!! $description !!}</div>
                </section>
            @endif

            @if ($keyIngredients)
                <section class="mt-10 border-t border-line pt-8" aria-labelledby="key-ingredients-title">
                    <h2 id="key-ingredients-title" class="heading-display text-3xl">{{ __('site.products.key_ingredients') }}</h2>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($keyIngredients as $ingredient)
                            <li class="rounded-2xl border border-line bg-paper-raised p-4">
                                <p class="font-semibold">
                                    {{ $ingredient['name'] }}
                                    @if ($ingredient['concentration'])
                                        <span class="ml-1 rounded-full bg-brand-soft px-2 py-0.5 text-xs font-semibold text-ink">{{ $ingredient['concentration'] }}</span>
                                    @endif
                                </p>
                                @if ($ingredient['benefit'])
                                    <p class="mt-1.5 text-sm text-ink-muted">{{ $ingredient['benefit'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($product->how_to_use)
                <section class="mt-10 border-t border-line pt-8" aria-labelledby="how-to-use-title">
                    <h2 id="how-to-use-title" class="heading-display text-3xl">{{ __('site.products.how_to_use') }}</h2>
                    <p class="mt-4 leading-relaxed whitespace-pre-line text-ink-muted">{{ $product->how_to_use }}</p>
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

            @if ($product->warnings)
                <section class="mt-10 rounded-2xl border border-line bg-paper-raised p-5" aria-labelledby="warnings-title">
                    <h2 id="warnings-title" class="flex items-center gap-2 text-sm font-semibold">
                        <svg viewBox="0 0 24 24" class="size-4 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" /></svg>
                        {{ __('site.products.warnings') }}
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-muted">{{ $product->warnings }}</p>
                </section>
            @endif
        </div>
    </article>

    @if ($product->testimonials->isNotEmpty())
        <section class="container-page mt-24" aria-labelledby="testimonials-title">
            <h2 id="testimonials-title" class="heading-display text-4xl">{{ __('site.products.testimonials') }}</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($product->testimonials as $testimonial)
                    <x-testimonial-card :testimonial="$testimonial" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($product->faqs->isNotEmpty())
        <section class="container-page mt-24" aria-labelledby="product-faq-title">
            <div class="max-w-3xl">
                <h2 id="product-faq-title" class="heading-display text-4xl">{{ __('site.products.faq') }}</h2>
                <x-faq-list :faqs="$product->faqs" class="mt-6" />
            </div>
        </section>
    @endif

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
