<x-layouts.guest title="Choose a new password">
    <h1 class="text-2xl">Choose a new password</h1>
    <p class="mt-1 text-sm text-muted">Use at least 8 characters with both letters and numbers.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field.input name="email" label="Email address" type="email" :value="$email" required />
        <x-field.input name="password" label="New password" type="password" autocomplete="new-password" required />
        <x-field.input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
        <button type="submit" class="btn-primary w-full py-2.5">Save new password</button>
    </form>
</x-layouts.guest>
