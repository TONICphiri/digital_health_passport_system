@props(['label', 'value', 'icon' => null, 'hint' => null, 'href' => null, 'tone' => 'default'])
@php
    $accent = match ($tone) {
        'warning' => 'border-l-gold-600',
        'danger' => 'border-l-red-700',
        default => 'border-l-brand-700',
    };
    $classes = "panel block border-l-4 {$accent} px-5 py-4";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes.' transition-colors hover:border-r-brand-600 hover:border-y-brand-600']) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    <div class="flex items-start justify-between gap-3">
        <p class="text-[13px] font-medium text-muted">{{ $label }}</p>
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 text-brand-700" />
        @endif
    </div>
    <p class="mt-2 text-3xl font-semibold tabular-nums text-ink">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-[13px] text-muted">{{ $hint }}</p>
    @endif
@if ($href)
    </a>
@else
    </div>
@endif
