<x-layouts.app title="Vaccine list">
    <x-page-header title="Vaccine list">
        <x-slot:actions><a href="{{ route('admin.vaccines.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add vaccine</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        @if ($vaccines->isEmpty())
            <x-empty title="No vaccines yet" icon="syringe" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Vaccine</th><th>Protects against</th><th>Recommended age</th><th class="text-right">Doses</th><th class="text-right">Days between doses</th><th class="text-right">Doses given</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($vaccines as $vaccine)
                            <tr>
                                <td class="font-medium">{{ $vaccine->name }}</td>
                                <td>{{ $vaccine->protects_against }}</td>
                                <td>{{ $vaccine->recommended_age ?? 'Any age' }}</td>
                                <td class="text-right tabular-nums">{{ $vaccine->total_doses }}</td>
                                <td class="text-right tabular-nums">{{ $vaccine->days_between_doses ?? 'Not applicable' }}</td>
                                <td class="text-right tabular-nums">{{ $vaccine->vaccinations_count }}</td>
                                <td><x-badge :tone="$vaccine->is_active ? 'success' : 'neutral'">{{ $vaccine->is_active ? 'In use' : 'Withdrawn' }}</x-badge></td>
                                <td class="text-right"><a href="{{ route('admin.vaccines.edit', $vaccine) }}" class="link text-sm">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
