@props(['vaccinations'])
<section class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Vaccinations</h2>
        @isset($action){{ $action }}@endisset
    </div>
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
