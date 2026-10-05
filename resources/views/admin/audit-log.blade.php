<x-layouts.app title="Activity log">
    <x-page-header title="Activity log" />

    <section class="panel">
        <x-search-bar placeholder="Search descriptions">
            <div><label for="from" class="label">From</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="input"></div>
            <div><label for="to" class="label">To</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="input"></div>
        </x-search-bar>

        @if ($logs->isEmpty())
            <x-empty title="No activity found" icon="shield" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date and time</th><th>User</th><th>Action</th><th>Facility</th><th>Address</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">{{ $log->created_at->format('j M Y, H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->facility?->name ?? 'National' }}</td>
                                <td class="mono text-muted">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        @endif
    </section>
</x-layouts.app>
