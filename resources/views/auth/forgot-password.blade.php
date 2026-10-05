<x-layouts.guest title="Reset your password">
    <h1 class="text-2xl">Reset your password</h1>
    <p class="mt-1 text-sm text-muted">Enter the email address on your account and we will send you a link to choose a new password.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-field.input name="email" label="Email address" type="email" autofocus required />
        <button type="submit" class="btn-primary w-full py-2.5">Send reset link</button>
    </form>

    <a href="{{ route('login') }}" class="link mt-6 inline-block text-sm">Back to sign in</a>
</x-layouts.guest>
