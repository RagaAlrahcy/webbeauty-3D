@props(['form', 'message'])

@if (session('form_sent') === $form)
    <div {{ $attributes->class('flex items-start gap-3 rounded-2xl border border-brand/30 bg-brand-soft px-5 py-4 text-sm') }} role="status">
        <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>
        <p>{{ $message }}</p>
    </div>
@endif
