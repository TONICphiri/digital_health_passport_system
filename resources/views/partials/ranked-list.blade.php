@if ($rows->isEmpty())
    <x-empty title="Nothing recorded in this period" icon="list" />
@else
    @php $top = max(1, $rows->max('total')); @endphp
    <ul>
        @foreach ($rows as $row)
            <li class="border-b border-line px-5 py-2.5 last:border-b-0">
                <div class="flex justify-between gap-3 text-sm"><span>{{ $row->{$labelKey} }}</span><span class="tabular-nums text-muted">{{ number_format($row->total) }} {{ \Illuminate\Support\Str::plural($unit, (int) $row->total) }}</span></div>
                <div class="mt-1.5 h-1.5 bg-paper"><div class="h-1.5 bg-brand-600" style="width: {{ round($row->total / $top * 100) }}%"></div></div>
            </li>
        @endforeach
    </ul>
@endif
