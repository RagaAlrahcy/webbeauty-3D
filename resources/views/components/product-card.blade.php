@props(['product'])

@php($image = $product->primaryImage())

<article class="group relative flex flex-col overflow-hidden rounded-[var(--radius-card)] border border-line bg-paper-raised transition duration-500 hover:-translate-y-1 hover:shadow-[0_24px_60px_-30px_rgba(80,40,50,0.35)]">
    <div class="relative aspect-square overflow-hidden" style="background: radial-gradient(120% 90% at 50% 15%, #fff 0%, {{ $product->base_color && preg_match('/^#[0-9a-fA-F]{6}$/', $product->base_color) ? $product->base_color.'33' : 'var(--brand-soft)' }} 100%)">
        @if ($image)
            <img
                src="{{ media_url($image, 'card') }}"
                srcset="{{ media_url($image, 'thumb') }} 400w, {{ media_url($image, 'card') }} 800w"
                sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                alt="{{ $product->name }}"
                class="size-full object-cover transition duration-700 group-hover:scale-105"
                width="800" height="800" loading="lazy" decoding="async"
            >
        @endif
        @if ($product->variants->isNotEmpty())
            <ul class="absolute bottom-4 left-4 flex gap-1.5" aria-label="{{ __('site.products.variants') }}">
                @foreach ($product->variants->take(5) as $variant)
                    <li class="size-4 rounded-full border-2 border-white shadow" style="background: {{ $variant->color }}" title="{{ $variant->label }}"></li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-2 p-6">
        <p class="eyebrow">{{ $product->category->name }}</p>
        <h3 class="heading-display text-2xl leading-tight">
            <a href="{{ localized_route('products.show', ['slug' => $product->slug]) }}" class="after:absolute after:inset-0">
                {{ $product->name }}
            </a>
        </h3>
        <p class="line-clamp-2 text-sm text-ink-muted">{{ $product->short_description }}</p>
        @if ($product->formattedPrice())
            <p class="mt-auto pt-3 text-sm font-semibold">{{ $product->formattedPrice() }}</p>
        @endif
    </div>
</article>
