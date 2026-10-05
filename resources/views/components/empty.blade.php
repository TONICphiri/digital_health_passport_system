@props(['title', 'icon' => 'info'])
<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <span class="mb-3 flex h-11 w-11 items-center justify-center border border-line bg-paper text-muted">
        <x-icon :name="$icon" />
    </span>
    <p class="font-medium text-ink">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-md text-sm text-muted">{{ $slot }}</div>
    @endif
</div>
