<section class="panel self-start">
    <div class="panel-header"><h2 class="panel-title">Account access</h2><x-status :value="$user->status" /></div>
    <div class="panel-body space-y-4 text-sm">
        <p class="text-muted">Last sign in: {{ $user->last_login_at?->format('j M Y, H:i') ?? 'Never' }}</p>
        <form method="POST" action="{{ route($resetRoute, $user) }}" onsubmit="return confirm('Create a new one time password for {{ $user->name }}?')">
            @csrf
            <button type="submit" class="btn-secondary w-full"><x-icon name="key" class="h-4 w-4" /> Reset password</button>
        </form>
        <form method="POST" action="{{ route($statusRoute, $user) }}" onsubmit="return confirm('{{ $user->isActive() ? 'Deactivate this account? The user will be signed out and unable to sign in.' : 'Activate this account?' }}')">
            @csrf @method('PATCH')
            <button type="submit" class="{{ $user->isActive() ? 'btn-danger' : 'btn-primary' }} w-full">{{ $user->isActive() ? 'Deactivate account' : 'Activate account' }}</button>
        </form>
    </div>
</section>
