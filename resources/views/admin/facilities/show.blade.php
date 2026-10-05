<x-layouts.app :title="$facility->name">
    <x-page-header :title="$facility->name" :description="$facility->type.', '.$facility->ownership.', '.$facility->district->name.' District'">
        <x-slot:breadcrumb><a href="{{ route('admin.facilities.index') }}" class="hover:text-brand-700">Facilities</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> {{ $facility->code }}</x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('admin.facilities.edit', $facility) }}" class="btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Edit</a>
            <form method="POST" action="{{ route('admin.facilities.status', $facility) }}" onsubmit="return confirm('{{ $facility->isActive() ? 'Deactivate this facility? Its staff will not be able to sign in.' : 'Activate this facility?' }}')">
                @csrf @method('PATCH')
                <button type="submit" class="{{ $facility->isActive() ? 'btn-danger' : 'btn-primary' }}">{{ $facility->isActive() ? 'Deactivate' : 'Activate' }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Patients registered here" :value="$facility->patients_count" icon="heart" />
        <x-stat label="Encounters recorded" :value="$facility->encounters_count" icon="stethoscope" />
        <x-stat label="Staff accounts" :value="$facility->users_count" icon="users" />
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-3">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Details</h2><x-status :value="$facility->status" /></div>
            <dl class="panel-body detail-list !grid-cols-1">
                <div><dt>Facility code</dt><dd class="mono">{{ $facility->code }}</dd></div>
                <div><dt>Address</dt><dd>{{ $facility->physical_address ?? 'Not recorded' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $facility->phone ?? 'Not recorded' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $facility->email ?? 'Not recorded' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $facility->created_at->format('j F Y') }}</dd></div>
            </dl>
        </section>

        <section class="panel lg:col-span-2">
            <div class="panel-header">
                <h2 class="panel-title">Facility Administrators</h2>
                <a href="{{ route('admin.facility-administrators.create', ['facility' => $facility->id]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Add administrator</a>
            </div>
            @forelse ($administrators as $admin)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <div><p class="font-medium">{{ $admin->name }}</p><p class="text-[13px] text-muted">{{ $admin->email }}</p></div>
                    <div class="flex items-center gap-3"><x-status :value="$admin->status" /><a href="{{ route('admin.facility-administrators.edit', $admin) }}" class="link text-sm">Manage</a></div>
                </div>
            @empty
                <x-empty title="This facility has no administrator yet" icon="users">Create an administrator so the facility can register its health workers.</x-empty>
            @endforelse

            <div class="border-t border-line px-5 py-4">
                <p class="text-[12px] font-medium uppercase tracking-wide text-muted">Accounts by role</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($staffByRole as $role => $count)
                        <span class="badge-neutral">{{ $role }}: {{ $count }}</span>
                    @empty
                        <span class="text-sm text-muted">No accounts yet.</span>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
