@php($whatsapp = $site->whatsappUrl())

<x-app-layout :title="__('site.contact.title')" :description="__('site.contact.intro')">
    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.contact.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('site.contact.intro') }}</p>
    </section>

    <div class="container-page mt-12 grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:gap-16">
        <aside class="flex flex-col gap-6">
            @if ($whatsapp)
                <div class="rounded-[var(--radius-card)] bg-ink p-7 text-paper">
                    <p class="text-xs font-semibold tracking-[0.22em] text-brand-soft uppercase">{{ __('site.contact.fastest') }}</p>
                    <p class="mt-3 text-paper/80">{{ __('site.contact.fastest_text') }}</p>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn mt-6 bg-paper text-ink hover:bg-brand-soft"
                       data-track="whatsapp_click" data-track-label="contact">WhatsApp</a>
                </div>
            @endif

            @if ($site->email || $site->phone || $site->address)
                <div class="rounded-[var(--radius-card)] border border-line bg-paper-raised p-7">
                    <h2 class="font-semibold">{{ __('site.contact.details') }}</h2>
                    <ul class="mt-4 space-y-3 text-sm text-ink-muted">
                        @if ($site->email)<li><a class="hover:text-brand" href="mailto:{{ $site->email }}">{{ $site->email }}</a></li>@endif
                        @if ($site->phone)<li><a class="hover:text-brand" href="tel:{{ preg_replace('/[^\d+]/', '', $site->phone) }}">{{ $site->phone }}</a></li>@endif
                        @if ($site->address)<li class="whitespace-pre-line">{{ $site->address }}</li>@endif
                    </ul>
                </div>
            @endif
        </aside>

        <section class="rounded-[var(--radius-card)] border border-line bg-paper-raised p-6 sm:p-8" aria-labelledby="contact-form-title">
            <h2 id="contact-form-title" class="heading-display text-3xl">{{ __('site.contact.form_title') }}</h2>

            <x-form-status form="contact" :message="__('site.contact.sent')" class="mt-6" />

            <form method="POST" action="{{ localized_route('contact.store') }}" class="relative mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                @csrf
                <x-honeypot />

                <x-form-field name="name" :label="__('site.forms.labels.name')" required autocomplete="name" maxlength="100" class="sm:col-span-2" />
                <x-form-field name="email" type="email" :label="__('site.forms.labels.email')" autocomplete="email" maxlength="255" />
                <x-form-field name="phone" type="tel" :label="__('site.forms.labels.phone')" autocomplete="tel" inputmode="tel" maxlength="30" />
                <p class="-mt-2 text-xs text-ink-muted sm:col-span-2">{{ __('site.forms.either_contact') }}</p>
                <x-form-field name="subject" :label="__('site.forms.labels.subject')" maxlength="150" class="sm:col-span-2" />
                <x-form-field name="message" type="textarea" :label="__('site.forms.labels.message')" required class="sm:col-span-2" />

                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary">{{ __('site.forms.send') }}</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
