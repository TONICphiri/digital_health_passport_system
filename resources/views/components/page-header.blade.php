@props(['title', 'description' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
    <div>
        @isset($breadcrumb)
            <div class="mb-2 flex items-center gap-1.5 text-[13px] text-muted">{{ $breadcrumb }}</div>
        @endisset
        <h1 class="text-2xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
