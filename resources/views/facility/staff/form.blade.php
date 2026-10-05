@php $editing = $user->exists; @endphp
<x-layouts.app :title="$editing ? 'Manage health worker' : 'Register health worker'">
    <x-page-header :title="$editing ? $user->name : 'Register a health worker'">
        <x-slot:breadcrumb><a href="{{ route('facility.staff.index') }}" class="hover:text-brand-700">Health workers</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $editing ? 'Manage' : 'New' }}</x-slot:breadcrumb>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $editing ? route('facility.staff.update', $user) : route('facility.staff.store') }}" class="panel lg:col-span-2">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.input name="name" label="Full name" :value="$user->name" class="sm:col-span-2" required />
                <x-field.input name="email" label="Email address" type="email" :value="$user->email" required />
                <x-field.input name="phone" label="Phone number" :value="$user->phone" />
                <x-field.input name="job_title" label="Job title" :value="$user->job_title" placeholder="For example, Clinical Officer or Nurse" />
                <x-field.input name="professional_registration_number" label="Professional registration number" :value="$user->professional_registration_number" class="sm:col-span-2" />
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('facility.staff.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">{{ $editing ? 'Save changes' : 'Create account' }}</button>
            </div>
        </form>

        @if ($editing)
            @include('partials.account-actions', ['user' => $user, 'statusRoute' => 'facility.staff.status', 'resetRoute' => 'facility.staff.reset-password'])
        @endif
    </div>
</x-layouts.app>
