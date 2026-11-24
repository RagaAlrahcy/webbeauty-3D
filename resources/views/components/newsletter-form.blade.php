@props(['privacyUrl' => null])

<div id="newsletter" {{ $attributes }}>
    <p class="font-semibold">{{ __('site.newsletter.title') }}</p>
    <p class="mt-1 text-sm text-ink-muted">{{ __('site.newsletter.intro') }}</p>

    @if (session('form_sent') === 'newsletter')
        <p class="mt-4 rounded-2xl bg-brand-soft px-4 py-3 text-sm" role="status">{{ __('site.newsletter.sent') }}</p>
    @else
        <form method="POST" action="{{ localized_route('newsletter.store') }}" class="mt-4">
            @csrf
            <x-honeypot />
            <div class="flex gap-2">
                <label for="newsletter-email" class="sr-only">{{ __('site.newsletter.placeholder') }}</label>
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    required
                    maxlength="255"
                    autocomplete="email"
                    value="{{ $errors->newsletter->any() ? old('email') : '' }}"
                    placeholder="{{ __('site.newsletter.placeholder') }}"
                    @error('email', 'newsletter') aria-invalid="true" aria-describedby="newsletter-error" @enderror
                    class="input min-w-0 flex-1"
                >
                <button type="submit" class="btn-primary shrink-0 px-5">{{ __('site.newsletter.submit') }}</button>
            </div>
            @error('email', 'newsletter')
                <p id="newsletter-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
            @if ($privacyUrl)
                <p class="mt-3 text-xs text-ink-muted">
                    {{ __('site.newsletter.consent') }}
                    <a href="{{ $privacyUrl }}" class="underline underline-offset-4 hover:text-brand">{{ \App\Enums\PageKey::Privacy->getLabel() }}</a>.
                </p>
            @endif
        </form>
    @endif
</div>
