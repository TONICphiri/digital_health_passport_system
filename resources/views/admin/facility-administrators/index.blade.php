<x-layouts.app title="Facility administrators">
    <x-page-header title="Facility administrators">
        <x-slot:actions>
            <a href="{{ route('admin.facility-administrators.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add administrator</a>
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        <x-search-bar placeholder="Name or email address">
            <select name="facility" class="input md:w-64" aria-label="Facility">
                <option value="">All facilities</option>
                @foreach ($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected(request('facility') == $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </x-search-bar>
        @include('partials.account-table', ['users' => $users, 'showFacility' => true, 'editRoute' => 'admin.facility-administrators.edit'])
    </section>
</x-layouts.app>
