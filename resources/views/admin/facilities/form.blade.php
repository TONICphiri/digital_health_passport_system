@php $editing = $facility->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit facility' : 'Register facility'">
    <x-page-header :title="$editing ? 'Edit '.$facility->name : 'Register a facility'">
        <x-slot:breadcrumb><a href="{{ route('admin.facilities.index') }}" class="hover:text-brand-700">Facilities</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.facilities.update', $facility) : route('admin.facilities.store') }}" class="panel max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.input name="name" label="Facility name" :value="$facility->name" class="sm:col-span-2" required />
            <x-field.input name="code" label="Facility code" :value="$facility->code" required />
            <x-field.select name="district_id" label="District" :options="$districts->pluck('name', 'id')->all()" :value="$facility->district_id" required />
            <x-field.select name="type" label="Facility type" :options="$facilityTypes" :value="$facility->type" required />
            <x-field.select name="ownership" label="Ownership" :options="$ownershipTypes" :value="$facility->ownership" required />
            <x-field.input name="physical_address" label="Physical address" :value="$facility->physical_address" class="sm:col-span-2" />
            <x-field.input name="phone" label="Phone number" :value="$facility->phone" />
            <x-field.input name="email" label="Email address" type="email" :value="$facility->email" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ $editing ? route('admin.facilities.show', $facility) : route('admin.facilities.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Register facility' }}</button>
        </div>
    </form>
</x-layouts.app>
