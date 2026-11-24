@php
    use App\Enums\ResellerBusinessType;

    $title = $page?->title ?: __('site.reseller.title');
    $intro = $page?->intro ?: __('site.reseller.intro');
    $content = $page?->safeContent();
    $businessTypes = collect(ResellerBusinessType::cases())
        ->mapWithKeys(fn (ResellerBusinessType $type): array => [$type->value => $type->getLabel()])
        ->all();
@endphp

<x-app-layout :title="$title" :description="$page?->metaDescription() ?: $intro">
    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ $title }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-ink-muted">{{ $intro }}</p>
    </section>

    <div class="container-page mt-12 grid gap-10 lg:grid-cols-[1fr_1.2fr] lg:gap-16">
        <div>
            @if ($content)
                <div class="prose-soft">{!! $content !!}</div>
            @endif
        </div>

        <section class="rounded-[var(--radius-card)] border border-line bg-paper-raised p-6 sm:p-8" aria-labelledby="reseller-form-title">
            <h2 id="reseller-form-title" class="heading-display text-3xl">{{ __('site.reseller.form_title') }}</h2>

            <x-form-status form="reseller" :message="__('site.reseller.sent')" class="mt-6" />

            <form method="POST" action="{{ localized_route('reseller.store') }}" class="relative mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                @csrf
                <x-honeypot />

                <x-form-field name="name" :label="__('site.forms.labels.name')" required autocomplete="name" maxlength="100" class="sm:col-span-2" />
                <x-form-field name="phone" type="tel" :label="__('site.forms.labels.phone')" required autocomplete="tel" inputmode="tel" maxlength="30" />
                <x-form-field name="email" type="email" :label="__('site.forms.labels.email')" autocomplete="email" maxlength="255" />
                <x-form-field name="city" :label="__('site.forms.labels.city')" required autocomplete="address-level2" maxlength="80" />
                <x-form-field name="business_type" type="select" :label="__('site.forms.labels.business_type')" :options="$businessTypes" required />
                <x-form-field name="shop_url" type="url" :label="__('site.forms.labels.shop_url')" maxlength="255" placeholder="https://" class="sm:col-span-2" />
                <x-form-field name="message" type="textarea" :rows="4" :label="__('site.forms.labels.reseller_message')" class="sm:col-span-2" />

                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary">{{ __('site.forms.send') }}</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
