@props(['name', 'label', 'value' => null, 'rows' => 3, 'help' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' input-error' : '')]) }}>{{ old($key, $value) }}</textarea>
    @error($key)
        <p class="field-error">{{ $message }}</p>
    @elseif ($help)
        <p class="field-help">{{ $help }}</p>
    @enderror
</div>
