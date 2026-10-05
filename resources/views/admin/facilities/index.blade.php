<x-layouts.app title="Facilities">
    <x-page-header title="Facilities">
        <x-slot:actions>
            <a href="{{ route('admin.facilities.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Register facility</a>
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        <x-search-bar placeholder="Facility name or code">
            <select name="district" class="input md:w-48" aria-label="District">
                <option value="">All districts</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected(request('district') == $district->id)>{{ $district->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input md:w-40" aria-label="Status">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-search-bar>

        @if ($facilities->isEmpty())
            <x-empty title="No facilities match your search" icon="building" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Facility</th><th>Type</th><th>District</th><th class="text-right">Staff</th><th class="text-right">Patients</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($facilities as $facility)
                            <tr>
                                <td><a href="{{ route('admin.facilities.show', $facility) }}" class="font-medium hover:text-brand-700 hover:underline">{{ $facility->name }}</a><p class="mono text-muted">{{ $facility->code }}</p></td>
                                <td>{{ $facility->type }}<p class="text-[13px] text-muted">{{ $facility->ownership }}</p></td>
                                <td>{{ $facility->district->name }}</td>
                                <td class="text-right tabular-nums">{{ $facility->users_count }}</td>
                                <td class="text-right tabular-nums">{{ $facility->patients_count }}</td>
                                <td><x-status :value="$facility->status" /></td>
                                <td class="whitespace-nowrap text-right"><a href="{{ route('admin.facilities.edit', $facility) }}" class="link text-sm">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $facilities->links() }}
        @endif
    </section>
</x-layouts.app>
