@php $editing = $district->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit district' : 'Add district'">
    <x-page-header :title="$editing ? 'Edit '.$district->name : 'Add a district'">
        <x-slot:breadcrumb><a href="{{ route('admin.districts.index') }}" class="hover:text-brand-700">Districts</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('admin.districts.update', $district) : route('admin.districts.store') }}" class="panel max-w-xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="panel-body space-y-5">
            <x-field.input name="name" label="District name" :value="$district->name" required />
            <x-field.select name="region" label="Region" :options="$regions" :value="$district->region" required />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('admin.districts.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save district</button>
        </div>
    </form>
</x-layouts.app>
