{{-- A filter bar for list pages. Extra filters go in the slot. --}}
@props(['placeholder' => 'Search', 'action' => null])
<form method="GET" action="{{ $action ?? url()->current() }}" class="flex flex-col gap-3 border-b border-line p-4 md:flex-row md:items-end">
    <div class="flex-1">
        <label for="search" class="sr-only">Search</label>
        <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}" class="input">
    </div>
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn-primary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
        @if (collect(request()->except('page'))->filter()->isNotEmpty())
            <a href="{{ url()->current() }}" class="btn-secondary">Clear</a>
        @endif
    </div>
</form>
