@props(['vaccinations', 'immunisations' => null])
<section class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Vaccinations</h2>
        @isset($action){{ $action }}@endisset
    </div>
    @if ($immunisations?->isNotEmpty())
        <div class="border-b border-line">
            <p class="px-5 pt-4 text-[11px] font-semibold uppercase tracking-wide text-muted">Immunisation status</p>
            <div class="divide-y divide-line">
                @foreach ($immunisations as $result)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $result->vaccine->name }}</p>
                            <p class="text-[13px] text-muted">
                                @if ($result->status === \App\Enums\ImmunisationStatus::Complete)
                                    All doses given
                                @elseif ($result->status === \App\Enums\ImmunisationStatus::NoSchedule)
                                    No schedule set for this age group
                                @else
                                    {{ $result->status === \App\Enums\ImmunisationStatus::NotYetDue ? 'Next dose due' : 'Next dose was due' }} {{ $result->dueOn->format('j M Y') }}
                                @endif
                            </p>
                        </div>
                        <x-badge :tone="$result->status->tone()">{{ $result->status->label() }}</x-badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    @if ($vaccinations->isEmpty())
        <x-empty title="No vaccinations recorded" icon="syringe" />
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Vaccine</th><th>Dose</th><th>Given</th><th>Next dose</th><th>Facility</th><th><span class="sr-only">Certificate</span></th></tr></thead>
                <tbody>
                    @foreach ($vaccinations as $vaccination)
                        <tr>
                            <td class="font-medium">{{ $vaccination->vaccine->name }}</td>
                            <td class="tabular-nums">{{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</td>
                            <td>{{ $vaccination->administered_on->format('j M Y') }}</td>
                            <td>{{ $vaccination->next_dose_due_on?->format('j M Y') ?? 'Complete' }}</td>
                            <td>{{ $vaccination->facility->name }}</td>
                            <td class="text-right"><a href="{{ route('certificates.show', $vaccination) }}" class="link text-[13px]" target="_blank" rel="noopener">Certificate</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
