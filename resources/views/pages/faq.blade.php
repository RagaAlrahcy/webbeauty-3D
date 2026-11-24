<x-app-layout :title="__('site.faq.title')" :description="__('site.faq.intro')">
    <section class="container-page pt-14 sm:pt-20">
        <div class="max-w-3xl">
            <p class="eyebrow">{{ $site->brand_name }}</p>
            <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.faq.title') }}</h1>
            <p class="mt-4 text-lg text-ink-muted">{{ __('site.faq.intro') }}</p>

            <x-faq-list :faqs="$faqs" class="mt-10" />

            <div class="mt-12 flex flex-wrap items-center gap-4 rounded-[var(--radius-card)] bg-brand-soft p-6">
                <p class="font-semibold">{{ __('site.faq.more') }}</p>
                <a href="{{ localized_route('contact') }}" class="btn-primary">{{ __('site.faq.contact') }}</a>
            </div>
        </div>
    </section>
</x-app-layout>
