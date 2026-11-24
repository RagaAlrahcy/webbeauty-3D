@php($whatsapp = $site->whatsappUrl())

<x-app-layout :title="__('site.stores.title')" :description="__('site.stores.intro')">
    <section class="container-page pt-14 sm:pt-20">
        <p class="eyebrow">{{ $site->brand_name }}</p>
        <h1 class="heading-display mt-4 text-5xl sm:text-6xl">{{ __('site.stores.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('site.stores.intro') }}</p>

        <div class="mt-8 flex max-w-3xl items-start gap-4 rounded-[var(--radius-card)] border border-brand/30 bg-brand-soft p-5" role="note">
            <svg viewBox="0 0 24 24" class="mt-0.5 size-6 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z" /><path d="M12 8v4M12 16h.01" /></svg>
            <div class="text-sm">
                <p class="font-semibold">{{ __('site.stores.warning_title') }}</p>
                <p class="mt-1 text-ink-muted">{{ __('site.stores.warning') }}</p>
            </div>
        </div>
    </section>

    @if ($marketplaces->isEmpty() && $offlineStores->isEmpty())
        <section class="container-page mt-12">
            <div class="rounded-[var(--radius-card)] border border-dashed border-line px-6 py-14 text-center">
                <p class="text-ink-muted">{{ __('site.stores.empty') }}</p>
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" class="btn-primary mt-6" target="_blank" rel="noopener" data-track="whatsapp_click" data-track-label="where-to-buy">{{ __('site.stores.ask') }}</a>
                @endif
            </div>
        </section>
    @endif

    @if ($marketplaces->isNotEmpty())
        <section class="container-page mt-14" aria-labelledby="marketplaces-title">
            <h2 id="marketplaces-title" class="heading-display text-4xl">{{ __('site.stores.marketplaces') }}</h2>
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($marketplaces as $store)
                    @php($logo = $store->getFirstMedia('logo'))
                    <li class="flex items-center gap-4 rounded-[var(--radius-card)] border border-line bg-paper-raised p-5">
                        <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-soft text-lg font-semibold">
                            @if ($logo)
                                <img src="{{ media_url($logo, 'thumb') }}" alt="" class="size-full object-contain p-1.5" width="56" height="56" loading="lazy">
                            @else
                                <span aria-hidden="true">{{ mb_substr($store->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $store->name }}</p>
                            @if ($store->platform)
                                <p class="text-sm text-ink-muted">{{ $store->platform->getLabel() }}</p>
                            @endif
                        </div>
                        @if ($store->url)
                            <a href="{{ $store->url }}" target="_blank" rel="noopener" class="btn-ghost shrink-0 px-4"
                               data-track="marketplace_click" data-track-label="{{ $store->name }}">
                                {{ __('site.stores.visit') }}<span class="sr-only">: {{ $store->name }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($offlineStores->isNotEmpty())
        <section class="container-page mt-16" aria-labelledby="offline-title">
            <h2 id="offline-title" class="heading-display text-4xl">{{ __('site.stores.offline') }}</h2>
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($offlineStores as $store)
                    <li class="flex flex-col gap-3 rounded-[var(--radius-card)] border border-line bg-paper-raised p-6">
                        @if ($store->city)
                            <p class="eyebrow">{{ $store->city }}</p>
                        @endif
                        <p class="text-lg font-semibold">{{ $store->name }}</p>
                        @if ($store->address)
                            <p class="text-sm whitespace-pre-line text-ink-muted">{{ $store->address }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-2 pt-2">
                            @if ($store->maps_url)
                                <a href="{{ $store->maps_url }}" target="_blank" rel="noopener" class="btn-ghost min-h-10 px-4 py-2">{{ __('site.stores.directions') }}</a>
                            @endif
                            @if ($store->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $store->phone) }}" class="btn-ghost min-h-10 px-4 py-2">{{ $store->phone }}</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
