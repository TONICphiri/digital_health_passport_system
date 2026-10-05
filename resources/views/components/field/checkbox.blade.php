@props(['name', 'label', 'checked' => false, 'help' => null])
@php $key = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex items-start gap-2.5 text-sm">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($key, $checked)) {{ $attributes->except('class') }}
            class="mt-0.5 h-4 w-4 border-line text-brand-700 focus:ring-brand-600">
        <span>
            <span class="font-medium text-ink">{{ $label }}</span>
            @if ($help)
                <span class="block text-[13px] text-muted">{{ $help }}</span>
            @endif
        </span>
    </label>
    @error($key)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
