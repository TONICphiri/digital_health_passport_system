@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
    @if ($type === 'password')
        <div x-data="{ show: false }" class="relative">
            <input id="{{ $id }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'" value=""
                @if ($required) required @endif
                {{ $attributes->except(['class', 'id'])->merge(['class' => 'input pr-11'.($errors->has($key) ? ' input-error' : '')]) }}
                @error($key) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror>
            <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" :title="show ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-ink">
                <span x-show="!show"><x-icon name="eye" class="h-5 w-5" /></span>
                <span x-show="show" x-cloak><x-icon name="eye-off" class="h-5 w-5" /></span>
            </button>
        </div>
    @else
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($key, $value) }}"
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' input-error' : '')]) }}
        @error($key) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror>
    @endif
    @error($key)
        <p id="{{ $id }}_error" class="field-error">{{ $message }}</p>
    @elseif ($help)
        <p class="field-help">{{ $help }}</p>
    @enderror
</div>
