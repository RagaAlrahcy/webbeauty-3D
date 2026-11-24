@php
    $name = $product->name;
    $background = $background();
@endphp

<div
    @if ($is3d()) data-product-viewer data-config='@json($config)' @endif
    role="group"
    aria-roledescription="{{ __('site.viewer.hint') }}"
    aria-label="{{ __('site.viewer.label', ['name' => $name]) }}"
    @class(['product-viewer', 'is-static' => ! $is3d(), $class])
    @if ($background) style="--viewer-bg: {{ $background }}" @endif
>
    @if ($image)
        <img
            src="{{ media_url($image, 'card') }}"
            srcset="{{ media_url($image, 'thumb') }} 400w, {{ media_url($image, 'card') }} 800w, {{ media_url($image, 'full') }} 1600w"
            sizes="(min-width: 1024px) 50vw, 100vw"
            alt="{{ $image->getCustomProperty('alt') ?: $name.' — '.$product->short_description }}"
            class="viewer-fallback"
            width="800"
            height="800"
            @if ($priority) fetchpriority="high" loading="eager" @else loading="lazy" @endif
            decoding="async"
        >
    @endif

    @if ($is3d())
        <p class="viewer-loading rounded-full bg-paper-raised/80 px-4 py-1.5 text-xs text-ink-muted backdrop-blur" aria-live="polite">
            <span class="sr-only">{{ __('site.viewer.loading') }}</span>
            <span aria-hidden="true" class="inline-flex gap-1">
                <span class="size-1.5 animate-pulse rounded-full bg-brand"></span>
                <span class="size-1.5 animate-pulse rounded-full bg-brand [animation-delay:150ms]"></span>
                <span class="size-1.5 animate-pulse rounded-full bg-brand [animation-delay:300ms]"></span>
            </span>
        </p>

        @if ($controls)
            <p class="viewer-hint pointer-events-none absolute top-4 left-4 rounded-full bg-paper-raised/75 px-3 py-1 text-xs text-ink-muted backdrop-blur">
                {{ __('site.viewer.hint') }}
            </p>

            <div class="viewer-controls absolute right-4 bottom-4 flex gap-2">
                @foreach ([
                    'zoom-in' => ['label' => 'Zoom in', 'icon' => 'M12 5v14M5 12h14'],
                    'zoom-out' => ['label' => 'Zoom out', 'icon' => 'M5 12h14'],
                    'reset' => ['label' => __('site.viewer.reset'), 'icon' => 'M4 4v6h6M20 20v-6h-6M5.5 15A7 7 0 0 0 18 17M18.5 9A7 7 0 0 0 6 7'],
                    'toggle-rotation' => ['label' => __('site.viewer.toggle_rotation'), 'icon' => 'M9 6v12M15 6v12'],
                ] as $action => $button)
                    <button
                        type="button"
                        data-viewer-action="{{ $action }}"
                        aria-label="{{ $button['label'] }}"
                        title="{{ $button['label'] }}"
                        @if ($action === 'toggle-rotation') aria-pressed="false" @endif
                        class="grid size-11 place-items-center rounded-full border border-line bg-paper-raised/85 text-ink shadow-sm backdrop-blur transition hover:border-brand hover:text-brand"
                    >
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="{{ $button['icon'] }}" />
                        </svg>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
