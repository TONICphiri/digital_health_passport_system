<x-layouts.app title="Districts">
    <x-page-header title="Districts">
        <x-slot:actions><a href="{{ route('admin.districts.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add district</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        @if ($districts->isEmpty())
            <x-empty title="No districts yet" icon="map" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>District</th><th>Region</th><th class="text-right">Facilities</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($districts as $district)
                            <tr>
                                <td class="font-medium">{{ $district->name }}</td>
                                <td>{{ $district->region }}</td>
                                <td class="text-right tabular-nums">{{ $district->facilities_count }}</td>
                                <td class="text-right"><a href="{{ route('admin.districts.edit', $district) }}" class="link text-sm">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
