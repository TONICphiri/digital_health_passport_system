@if ($activity->isEmpty())
    <x-empty title="No activity recorded yet" icon="shield" />
@else
    <ul class="divide-y divide-line">
        @foreach ($activity as $entry)
            <li class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-3 text-sm">
                <span><span class="font-medium">{{ $entry->user?->name ?? 'System' }}</span> <span class="text-muted">{{ $entry->description }}</span></span>
                <time class="text-[13px] text-muted" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->diffForHumans() }}</time>
            </li>
        @endforeach
    </ul>
@endif
