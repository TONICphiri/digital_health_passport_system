<x-layouts.app title="Facility reports">
    <x-page-header :title="'Reports for '.$facility->name">
        <x-slot:breadcrumb>{{ $from->format('j F Y') }} to {{ $to->format('j F Y') }}</x-slot:breadcrumb>
        <x-slot:actions><button type="button" onclick="window.print()" class="btn-secondary no-print"><x-icon name="printer" class="h-4 w-4" /> Print</button></x-slot:actions>
    </x-page-header>

    <form method="GET" class="panel no-print mb-6 flex flex-wrap items-end gap-3 p-4">
        <div><label for="from" class="label">From</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label for="to" class="label">To</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <button type="submit" class="btn-primary">Show report</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Passports issued" :value="$report['passports_issued']" icon="user-plus" />
        <x-stat label="Passports opened" :value="$report['passports_opened']" icon="qr" :hint="$report['scan_rate'].'% opened by scanning the card'" />
        <x-stat label="Encounters" :value="$report['encounters']" icon="stethoscope" />
        <x-stat label="Vaccinations" :value="$report['vaccinations']" icon="syringe" />
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Encounters per day</h2></div>
        @php $max = max(1, $report['daily_encounters']->max() ?? 1); @endphp
        @if ($report['daily_encounters']->sum() === 0)
            <x-empty title="No encounters in this period" icon="chart" />
        @else
            <div class="flex h-48 items-end gap-1 overflow-x-auto px-5 pb-2 pt-4" role="img" aria-label="Encounters per day">
                @foreach ($report['daily_encounters'] as $day => $total)
                    <div class="flex min-w-[18px] flex-1 flex-col items-center justify-end gap-1" title="{{ \Illuminate\Support\Carbon::parse($day)->format('j M') }}: {{ $total }}">
                        <span class="text-[11px] tabular-nums text-muted">{{ $total > 0 ? $total : '' }}</span>
                        <div class="w-full {{ $total > 0 ? 'bg-brand-600' : 'bg-line' }}" style="height: {{ $total > 0 ? max(4, round($total / $max * 140)) : 1 }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-line px-5 py-2 text-[12px] text-muted">
                <span>{{ \Illuminate\Support\Carbon::parse($report['daily_encounters']->keys()->first())->format('j M') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse($report['daily_encounters']->keys()->last())->format('j M') }}</span>
            </div>
        @endif
    </section>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Most common diagnoses</h2></div>
        @include('partials.ranked-list', ['rows' => $report['top_diagnoses'], 'labelKey' => 'diagnosis', 'unit' => 'encounter'])
    </section>
</x-layouts.app>
