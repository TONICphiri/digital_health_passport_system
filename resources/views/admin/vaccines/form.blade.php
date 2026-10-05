@php $editing = $vaccine->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit vaccine' : 'Add vaccine'">
    <x-page-header :title="$editing ? 'Edit '.$vaccine->name : 'Add a vaccine'">
        <x-slot:breadcrumb><a href="{{ route('admin.vaccines.index') }}" class="hover:text-brand-700">Vaccine list</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Edit' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ $editing ? route('admin.vaccines.update', $vaccine) : route('admin.vaccines.store') }}" class="panel max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.input name="name" label="Vaccine name" :value="$vaccine->name" required />
            <x-field.input name="protects_against" label="Protects against" :value="$vaccine->protects_against" required />
            <x-field.input name="total_doses" label="Number of doses" type="number" min="1" max="10" :value="$vaccine->total_doses" required />
            <x-field.input name="days_between_doses" label="Days between doses" type="number" min="1" :value="$vaccine->days_between_doses" />
            <x-field.input name="recommended_age" label="Recommended age" :value="$vaccine->recommended_age" placeholder="For example, at birth" class="sm:col-span-2" />
            <x-field.checkbox name="is_active" label="In use" :checked="$vaccine->is_active" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('admin.vaccines.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save vaccine</button>
        </div>
    </form>
</x-layouts.app>
