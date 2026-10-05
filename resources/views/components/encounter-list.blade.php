@props(['encounters'])
<section class="panel">
    <div class="panel-header"><h2 class="panel-title">Encounters</h2></div>
    @forelse ($encounters as $encounter)
        <article class="border-b border-line px-5 py-4 last:border-b-0">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="font-medium">{{ $encounter->diagnosis ?: $encounter->reason }}</p>
                    <p class="text-[13px] text-muted">{{ $encounter->reason }}</p>
                </div>
                <p class="text-right text-[13px] text-muted">{{ $encounter->recorded_at->format('j M Y, H:i') }}<br>{{ $encounter->facility->name }}</p>
            </div>
            @php
                $vitals = collect([
                    'Temperature' => $encounter->temperature ? $encounter->temperature.' °C' : null,
                    'Blood pressure' => $encounter->bloodPressure(),
                    'Pulse' => $encounter->pulse_rate ? $encounter->pulse_rate.' per minute' : null,
                    'Oxygen' => $encounter->oxygen_saturation ? $encounter->oxygen_saturation.'%' : null,
                    'Weight' => $encounter->weight ? $encounter->weight.' kg' : null,
                    'Height' => $encounter->height ? $encounter->height.' cm' : null,
                ])->filter();
            @endphp
            @if ($vitals->isNotEmpty())
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                    @foreach ($vitals as $label => $value)
                        <div class="flex gap-1.5"><dt class="text-muted">{{ $label }}</dt><dd class="font-medium tabular-nums">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @if ($encounter->treatment_summary)
                <p class="mt-3 text-sm">{{ $encounter->treatment_summary }}</p>
            @endif
            @if ($encounter->medications->isNotEmpty())
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($encounter->medications as $medication)
                        <li><span class="font-medium">{{ $medication->name }}</span> <span class="text-muted">{{ $medication->directions() }}</span></li>
                    @endforeach
                </ul>
            @endif
            @if ($encounter->follow_up_on)
                <p class="mt-3 text-[13px] text-muted">Follow up on {{ $encounter->follow_up_on->format('j M Y') }}</p>
            @endif
            <p class="mt-2 text-[13px] text-muted">{{ $encounter->recordedBy?->name }}</p>
        </article>
    @empty
        <x-empty title="No encounters yet" icon="stethoscope" />
    @endforelse
</section>
