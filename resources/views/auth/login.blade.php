<x-layouts.guest title="Sign in">
    <h1 class="text-2xl">Sign in</h1>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" novalidate>
        @csrf
        <x-field.input name="email" label="Email address" type="email" autocomplete="username" autofocus required />
        <x-field.input name="password" label="Password" type="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 border-line text-brand-700 focus:ring-brand-600">
                Keep me signed in
            </label>
            <a href="{{ route('password.request') }}" class="link text-sm">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary w-full py-2.5">Sign in</button>
    </form>

</x-layouts.guest>
