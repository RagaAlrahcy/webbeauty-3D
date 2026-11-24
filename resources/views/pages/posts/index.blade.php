<x-app-layout :title="__('site.posts.title')" :description="__('site.posts.intro')">
    @if ($posts->currentPage() > 1)
        @push('head')
            <meta name="robots" content="noindex, follow">
        @endpush
    @endif

    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.posts.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('site.posts.intro') }}</p>
    </section>

    <section class="container-page mt-12">
        @if ($posts->isEmpty())
            <p class="rounded-[var(--radius-card)] border border-dashed border-line px-6 py-16 text-center text-ink-muted">{{ __('site.posts.empty') }}</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="mt-12">{{ $posts->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
