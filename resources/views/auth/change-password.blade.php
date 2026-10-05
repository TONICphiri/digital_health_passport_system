<x-layouts.app title="Change password">
    <x-page-header title="Change password" />

    @if ($forced)
        <div class="mb-5 border border-gold-600/40 border-l-4 border-l-gold-600 bg-gold-100 px-4 py-3 text-sm">
            You are signed in with a one time password. Choose your own password to continue.
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}" class="panel max-w-xl">
        @csrf
        @method('PUT')
        <div class="panel-body space-y-5">
            <x-field.input name="current_password" label="Current password" type="password" autocomplete="current-password" required />
            <x-field.input name="password" label="New password" type="password" autocomplete="new-password" help="At least 8 characters, with letters and numbers" required />
            <x-field.input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
        </div>
        <div class="flex justify-end border-t border-line px-5 py-3">
            <button type="submit" class="btn-primary">Save password</button>
        </div>
    </form>
</x-layouts.app>
