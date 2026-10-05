<x-layouts.app title="My profile">
    <x-page-header title="My profile" />

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('profile.update') }}" class="panel lg:col-span-2">
            @csrf
            @method('PUT')
            <div class="panel-header"><h2 class="panel-title">Contact details</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.input name="name" label="Full name" :value="$user->name" class="sm:col-span-2" required />
                <x-field.input name="email" label="Email address" type="email" :value="$user->email" required />
                <x-field.input name="phone" label="Phone number" :value="$user->phone" />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-3">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Account</h2></div>
            <dl class="panel-body space-y-3 text-sm">
                <div><dt class="text-[12px] uppercase tracking-wide text-muted">Role</dt><dd class="mt-0.5"><span class="badge-{{ $user->role()?->tone() ?? 'neutral' }}">{{ $user->roleLabel() }}</span></dd></div>
                @if ($user->facility)
                    <div><dt class="text-[12px] uppercase tracking-wide text-muted">Facility</dt><dd class="mt-0.5">{{ $user->facility->name }}</dd></div>
                @endif
                @if ($user->professional_registration_number)
                    <div><dt class="text-[12px] uppercase tracking-wide text-muted">Registration number</dt><dd class="mono mt-0.5">{{ $user->professional_registration_number }}</dd></div>
                @endif
                <div><dt class="text-[12px] uppercase tracking-wide text-muted">Last sign in</dt><dd class="mt-0.5">{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Not recorded' }}</dd></div>
            </dl>
            <div class="border-t border-line px-5 py-3">
                <a href="{{ route('password.change') }}" class="btn-secondary w-full">Change password</a>
            </div>
        </section>
    </div>
</x-layouts.app>
