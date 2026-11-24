@props(['post'])

@php($cover = $post->getFirstMedia('cover'))

<article class="group relative flex flex-col overflow-hidden rounded-[var(--radius-card)] border border-line bg-paper-raised transition duration-500 hover:-translate-y-1 hover:shadow-[0_24px_60px_-30px_rgba(80,40,50,0.35)]">
    <div class="aspect-[16/10] overflow-hidden bg-brand-soft">
        @if ($cover)
            <img src="{{ media_url($cover, 'card') }}" alt="" class="size-full object-cover transition duration-700 group-hover:scale-105"
                 width="800" height="500" loading="lazy" decoding="async">
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-3 p-6">
        <p class="text-xs text-ink-muted">
            <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('j F Y') }}</time>
            · {{ __('site.posts.minutes', ['count' => $post->readingMinutes()]) }}
        </p>
        <h3 class="heading-display text-2xl leading-tight">
            <a href="{{ localized_route('posts.show', ['slug' => $post->slug]) }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
        </h3>
        <p class="line-clamp-3 text-sm text-ink-muted">{{ $post->excerpt }}</p>
        <p class="mt-auto pt-2 text-sm font-semibold text-brand">{{ __('site.posts.read_more') }} →</p>
    </div>
</article>
