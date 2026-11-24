<x-app-layout :title="$page->title" :description="$page->metaDescription()">
    <article class="container-page pt-14 sm:pt-20">
        <div class="max-w-3xl">
            <p class="eyebrow">{{ $site->brand_name }}</p>
            <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ $page->title }}</h1>
            @if ($page->intro)
                <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $page->intro }}</p>
            @endif

            @if ($content = $page->safeContent())
                <div class="prose-soft mt-10 border-t border-line pt-10">{!! $content !!}</div>
            @endif

            <p class="mt-12 text-xs text-ink-muted">
                <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->translatedFormat('j F Y') }}</time>
            </p>
        </div>
    </article>
</x-app-layout>
