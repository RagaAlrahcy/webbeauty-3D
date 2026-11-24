@props(['faqs'])

{{-- Native <details> works without JavaScript and is keyboard accessible. --}}
<div {{ $attributes->class('divide-y divide-line border-y border-line') }}>
    @foreach ($faqs as $faq)
        <details class="group py-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-base font-semibold [&::-webkit-details-marker]:hidden">
                {{ $faq->question }}
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-brand transition group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            </summary>
            <p class="mt-3 leading-relaxed whitespace-pre-line text-ink-muted">{{ $faq->answer }}</p>
        </details>
    @endforeach
</div>
