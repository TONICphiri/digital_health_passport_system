@php $editing = $user->exists; @endphp
<x-layouts.app :title="$editing ? 'Manage administrator' : 'Add administrator'">
    <x-page-header :title="$editing ? $user->name : 'Add a facility administrator'">
        <x-slot:breadcrumb><a href="{{ route('admin.facility-administrators.index') }}" class="hover:text-brand-700">Facility administrators</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Manage' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $editing ? route('admin.facility-administrators.update', $user) : route('admin.facility-administrators.store') }}" class="panel lg:col-span-2">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.input name="name" label="Full name" :value="$user->name" class="sm:col-span-2" required />
                <x-field.input name="email" label="Email address" type="email" :value="$user->email" required />
                <x-field.input name="phone" label="Phone number" :value="$user->phone" />
                <x-field.select name="facility_id" label="Facility" :options="$facilities->pluck('name', 'id')->all()" :value="$user->facility_id" class="sm:col-span-2" required />
                <x-field.input name="job_title" label="Job title" :value="$user->job_title" placeholder="For example, Hospital Administrator" class="sm:col-span-2" />
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('admin.facility-administrators.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Create account' }}</button>
            </div>
        </form>

        @if ($editing)
            @include('partials.account-actions', ['user' => $user, 'statusRoute' => 'admin.facility-administrators.status', 'resetRoute' => 'admin.facility-administrators.reset-password'])
        @endif
    </div>
</x-layouts.app>
