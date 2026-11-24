<x-app-layout :title="__('site.products.title')" :description="__('site.products.intro')">
    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.products.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('site.products.intro') }}</p>

        @if ($categories->isNotEmpty())
            <nav class="mt-10" aria-label="{{ __('site.home.categories') }}">
                <ul class="flex flex-wrap gap-2">
                    <li>
                        <a href="{{ localized_route('products.index') }}"
                           @if (! $activeCategory) aria-current="page" @endif
                           @class(['btn min-h-10 border px-5 py-2', 'border-ink bg-ink text-paper' => ! $activeCategory, 'border-line hover:border-brand' => $activeCategory])>
                            {{ __('site.products.all') }}
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        @php($active = $activeCategory === $category->slug)
                        <li>
                            <a href="{{ localized_route('products.index', ['category' => $category->slug]) }}"
                               @if ($active) aria-current="page" @endif
                               @class(['btn min-h-10 border px-5 py-2', 'border-ink bg-ink text-paper' => $active, 'border-line hover:border-brand' => ! $active])>
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </section>

    <section class="container-page mt-10" aria-live="polite">
        @if ($products->isEmpty())
            <p class="rounded-[var(--radius-card)] border border-dashed border-line px-6 py-16 text-center text-ink-muted">{{ __('site.products.empty') }}</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
</x-app-layout>
