<x-layouts.app title="Health workers">
    <x-page-header title="Health workers">
        <x-slot:actions><a href="{{ route('facility.staff.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register health worker</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        <x-search-bar placeholder="Name or email address" />
        @include('partials.account-table', ['users' => $staff, 'showFacility' => false, 'editRoute' => 'facility.staff.edit'])
    </section>
</x-layouts.app>
