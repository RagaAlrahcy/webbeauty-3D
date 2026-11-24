@php
    $icons = [
        'danger' => 'heroicon-o-exclamation-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'info' => 'heroicon-o-information-circle',
    ];
    $counts = collect($issues)->countBy('level');
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('admin.dashboard.health.heading')"
        :description="$issues === []
            ? __('admin.dashboard.health.all_good_description')
            : __('admin.dashboard.health.summary', [
                'danger' => $counts->get('danger', 0),
                'warning' => $counts->get('warning', 0),
                'info' => $counts->get('info', 0),
            ])"
        icon="heroicon-o-clipboard-document-check"
        :icon-color="$counts->has('danger') ? 'danger' : ($counts->has('warning') ? 'warning' : 'success')"
        collapsible
    >
        @if ($issues === [])
            <x-filament::callout
                color="success"
                icon="heroicon-o-check-circle"
                :heading="__('admin.dashboard.health.all_good')"
            />
        @else
            {{-- Panel CSS is precompiled, so the grid uses inline styles instead of Tailwind classes. --}}
            <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr));">
                @foreach ($issues as $issue)
                    <x-filament::callout
                        :color="$issue['level']"
                        :icon="$icons[$issue['level']]"
                        :heading="$issue['title']"
                        :description="$issue['description']"
                    >
                        @if ($issue['items'] !== [])
                            <x-slot name="footer">
                                <ul style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    @foreach ($issue['items'] as $item)
                                        <li>
                                            <x-filament::link :href="$item['url']" size="sm" icon="heroicon-m-pencil-square">
                                                {{ $item['label'] }}
                                            </x-filament::link>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-slot>
                        @endif

                        <x-slot name="controls">
                            <x-filament::link :href="$issue['url']" size="sm" icon="heroicon-m-arrow-right" icon-position="after">
                                {{ __('admin.dashboard.health.fix') }}
                            </x-filament::link>
                        </x-slot>
                    </x-filament::callout>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
