@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'options' => [],
    'rows' => 5,
    'hint' => null,
])

@php
    $id = 'field-'.$name;
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp

<div {{ $attributes->only('class')->class('flex flex-col gap-2') }}>
    <label for="{{ $id }}" class="text-sm font-semibold">
        {{ $label }}
        <span class="font-normal text-ink-muted">({{ $required ? __('site.forms.required') : __('site.forms.optional') }})</span>
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            class="input">{{ old($name) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @required($required)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            class="input">
            <option value="">{{ __('site.forms.choose') }}</option>
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected(old($name) === (string) $value)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" @required($required)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->only(['autocomplete', 'inputmode', 'maxlength', 'placeholder']) }}
            class="input">
    @endif

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
