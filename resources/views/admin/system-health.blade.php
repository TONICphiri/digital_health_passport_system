<x-layouts.app title="System health">
    <x-page-header title="System health" >
        <x-slot:actions><a href="{{ route('admin.system-health') }}" class="btn-secondary">Run checks again</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Facilities" :value="$totals['facilities']" icon="building" />
        <x-stat label="User accounts" :value="$totals['users']" icon="users" />
        <x-stat label="Patient records" :value="$totals['patients']" icon="heart" />
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Checks</h2></div>
        <table class="table">
            <thead><tr><th>Check</th><th>Result</th><th>Condition</th></tr></thead>
            <tbody>
                @foreach ($checks as $check)
                    <tr>
                        <td class="font-medium">{{ $check['label'] }}</td>
                        <td>{{ $check['value'] }}</td>
                        <td><x-badge :tone="$check['healthy'] ? 'success' : 'danger'">{{ $check['healthy'] ? 'Working' : 'Needs attention' }}</x-badge></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-layouts.app>
