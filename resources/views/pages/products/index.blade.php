@php
    use App\Enums\ProductSort;

    $categoryUrl = fn (?string $slug): string => localized_route('products.index', $filters->toQuery(['category' => $slug]));
@endphp

<x-app-layout :title="__('site.products.title')" :description="__('site.products.intro')">
    @if ($filters->isFiltered() || $products->currentPage() > 1)
        @push('head')
            <meta name="robots" content="noindex, follow">
        @endpush
    @endif

    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.products.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('site.products.intro') }}</p>

        @if ($categories->isNotEmpty())
            <nav class="mt-10" aria-label="{{ __('site.home.categories') }}">
                <ul class="flex flex-wrap gap-2">
                    <li>
                        <a href="{{ $categoryUrl(null) }}"
                           @if (! $filters->category) aria-current="page" @endif
                           @class(['btn min-h-10 border px-5 py-2', 'border-ink bg-ink text-paper' => ! $filters->category, 'border-line hover:border-brand' => $filters->category])>
                            {{ __('site.products.all') }}
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        @php($active = $filters->category === $category->slug)
                        <li>
                            <a href="{{ $categoryUrl($category->slug) }}"
                               @if ($active) aria-current="page" @endif
                               @class(['btn min-h-10 border px-5 py-2', 'border-ink bg-ink text-paper' => $active, 'border-line hover:border-brand' => ! $active])>
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        {{-- Plain GET form: works without JavaScript; selects submit on change when Alpine is available. --}}
        <form method="GET" action="{{ localized_route('products.index') }}" role="search" aria-label="{{ __('site.products.filters') }}"
              class="mt-6 grid gap-3 rounded-[var(--radius-card)] border border-line bg-paper-raised p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
            @if ($filters->category)
                <input type="hidden" name="category" value="{{ $filters->category }}">
            @endif

            <div>
                <label for="filter-q" class="sr-only">{{ __('site.products.search') }}</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters->search }}" maxlength="{{ \App\Support\ProductFilters::MAX_SEARCH_LENGTH }}"
                       placeholder="{{ __('site.products.search_placeholder') }}" class="input">
            </div>

            @if ($facets['skinTypes'])
                <div>
                    <label for="filter-skin" class="sr-only">{{ __('site.products.skin_type') }}</label>
                    <select id="filter-skin" name="skin" class="input" x-data x-on:change="$el.form.requestSubmit()">
                        <option value="">{{ __('site.products.any_skin_type') }}</option>
                        @foreach ($facets['skinTypes'] as $skinType)
                            <option value="{{ $skinType->value }}" @selected($filters->skinType === $skinType)>{{ $skinType->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($facets['concerns'])
                <div>
                    <label for="filter-concern" class="sr-only">{{ __('site.products.concern') }}</label>
                    <select id="filter-concern" name="concern" class="input" x-data x-on:change="$el.form.requestSubmit()">
                        <option value="">{{ __('site.products.any_concern') }}</option>
                        @foreach ($facets['concerns'] as $concern)
                            <option value="{{ $concern->value }}" @selected($filters->concern === $concern)>{{ $concern->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="filter-sort" class="sr-only">{{ __('site.products.sort') }}</label>
                <select id="filter-sort" name="sort" class="input" x-data x-on:change="$el.form.requestSubmit()">
                    @foreach (ProductSort::cases() as $sort)
                        <option value="{{ $sort === ProductSort::Recommended ? '' : $sort->value }}" @selected($filters->sort === $sort)>{{ __('site.products.sort') }}: {{ $sort->getLabel() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">{{ __('site.products.apply') }}</button>
        </form>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted">
            <p aria-live="polite">{{ trans_choice('site.products.results', $products->total(), ['count' => $products->total()]) }}</p>
            @if ($filters->isFiltered())
                <a href="{{ localized_route('products.index') }}" class="font-semibold text-brand underline-offset-4 hover:underline">{{ __('site.products.reset') }}</a>
            @endif
        </div>
    </section>

    <section class="container-page mt-8">
        @if ($products->isEmpty())
            <p class="rounded-[var(--radius-card)] border border-dashed border-line px-6 py-16 text-center text-ink-muted">
                {{ $filters->isFiltered() ? __('site.products.no_results') : __('site.products.empty') }}
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="mt-12">{{ $products->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
