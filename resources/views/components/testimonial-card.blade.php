@props(['testimonial', 'showProduct' => false])

@php
    $photo = $testimonial->getFirstMedia('photo');
    $before = $testimonial->getFirstMedia('before');
    $after = $testimonial->getFirstMedia('after');
@endphp

<figure {{ $attributes->class('flex flex-col gap-5 rounded-[var(--radius-card)] border border-line bg-paper-raised p-6 sm:p-7') }}>
    @if ($before && $after)
        <div class="grid grid-cols-2 gap-2">
            @foreach ([[$before, __('site.testimonials.before')], [$after, __('site.testimonials.after')]] as [$media, $caption])
                <div class="relative overflow-hidden rounded-2xl bg-brand-soft">
                    <img src="{{ media_url($media, 'card') }}" alt="{{ $caption }} — {{ $testimonial->customer_name }}"
                         class="aspect-square w-full object-cover" width="400" height="400" loading="lazy" decoding="async">
                    <span class="absolute bottom-2 left-2 rounded-full bg-paper-raised/90 px-2.5 py-1 text-xs font-semibold">{{ $caption }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($testimonial->rating)
        <p class="flex gap-0.5 text-brand" role="img" aria-label="{{ __('site.testimonials.rating', ['rating' => $testimonial->rating]) }}">
            @for ($star = 1; $star <= 5; $star++)
                <svg viewBox="0 0 20 20" class="size-4" aria-hidden="true" fill="{{ $star <= $testimonial->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.2">
                    <path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.6L10 14.7l-5.1 2.7 1-5.6-4.1-4 5.7-.8z" />
                </svg>
            @endfor
        </p>
    @endif

    <blockquote class="flex-1 text-base leading-relaxed text-ink">“{{ $testimonial->content }}”</blockquote>

    <figcaption class="flex items-center gap-3 text-sm">
        @if ($photo)
            <img src="{{ media_url($photo, 'thumb') }}" alt="" class="size-10 rounded-full object-cover" width="40" height="40" loading="lazy">
        @endif
        <span>
            <span class="font-semibold">{{ $testimonial->customer_name }}</span>
            @if ($testimonial->customer_info)
                <span class="block text-ink-muted">{{ $testimonial->customer_info }}</span>
            @endif
        </span>
    </figcaption>

    @if ($before && $after)
        <p class="text-xs text-ink-muted">{{ __('site.testimonials.disclaimer') }}</p>
    @endif
</figure>
