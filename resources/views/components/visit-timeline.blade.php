@props(['visit'])
@php
    $stageTypes = \App\Enums\VisitStageType::cases();
    $positionLabel = fn (?string $value) => $value ? \App\Enums\Position::tryFrom($value)?->label() : null;
@endphp

<div {{ $attributes }}>
    @foreach ($stageTypes as $type)
        @php $stage = $visit->stage($type); @endphp
        <div class="border-b border-line px-5 py-3 last:border-b-0">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium">{{ $type->label() }}</p>
                @if ($stage)
                    <x-badge tone="success">Recorded</x-badge>
                @else
                    <x-badge tone="neutral">Waiting</x-badge>
                @endif
            </div>

            @if ($stage)
                @if ($stage->summary)
                    <p class="mt-1 text-sm">{{ $stage->summary }}</p>
                @endif

                @if ($type === \App\Enums\VisitStageType::Triage && ! empty($stage->payload['tests']))
                    <ul class="mt-1 space-y-0.5 text-sm">
                        @foreach ($stage->payload['tests'] as $test)
                            <li><span class="font-medium">{{ $test['name'] }}</span>@if (! empty($test['result'])): <span>{{ $test['result'] }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif

                <p class="mt-1 text-[13px] text-muted">
                    Recorded by {{ $stage->recordedBy?->name ?? 'an unknown user' }}@if ($positionLabel($stage->recorded_as_position)), {{ $positionLabel($stage->recorded_as_position) }}@endif
                    on {{ $stage->recorded_at->format('j M Y, H:i') }}
                </p>

                @if ($stage->edited_at)
                    <p class="text-[13px] text-muted">Changed by {{ $stage->editedBy?->name ?? 'an unknown user' }} on {{ $stage->edited_at->format('j M Y, H:i') }}</p>
                @endif
            @endif
        </div>
    @endforeach

    @if ($visit->observations->isNotEmpty())
        <div class="border-b border-line px-5 py-3">
            <p class="text-sm font-medium">Vital signs</p>
            <ul class="mt-1 space-y-1 text-sm">
                @foreach ($visit->latestObservations() as $observation)
                    <li>
                        {{ $observation->label() }}: <span class="font-medium">{{ $observation->value }}</span>@if ($observation->unit()) {{ $observation->unit() }}@endif
                        <span class="text-[13px] text-muted">— {{ $observation->recordedBy?->name ?? 'an unknown user' }}@if ($positionLabel($observation->recorded_as_position)), {{ $positionLabel($observation->recorded_as_position) }}@endif, {{ $observation->recorded_at->format('j M Y, H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($visit->medications->isNotEmpty())
        <div class="px-5 py-3">
            <p class="text-sm font-medium">Medicines</p>
            <ul class="mt-1 space-y-2 text-sm">
                @foreach ($visit->medications as $medication)
                    <li class="flex items-start justify-between gap-3">
                        <span><span class="font-medium">{{ $medication->name }}</span> <span class="text-muted">{{ $medication->directions() }}</span></span>
                        <span class="shrink-0 text-[13px] text-muted">
                            @if ($medication->dispensed)
                                Handed over{{ $medication->dispensedBy ? ' by '.$medication->dispensedBy->name : '' }}
                            @else
                                Waiting
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
