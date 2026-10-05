{{-- Options may be a plain list of values or a value to label map. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => 'Choose an option', 'help' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
    $selected = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
    $isList = array_is_list($options);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' input-error' : '')]) }}>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @php $optionValue = $isList ? $optionLabel : $optionValue; @endphp
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($key)
        <p class="field-error">{{ $message }}</p>
    @elseif ($help)
        <p class="field-help">{{ $help }}</p>
    @enderror
</div>
