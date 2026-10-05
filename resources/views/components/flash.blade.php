{{-- Messages shown after an action: success, business rule errors and one time passwords. --}}
@if (session('success'))
    <div class="mb-5 flex items-start gap-3 border border-brand-200 border-l-4 border-l-brand-700 bg-brand-50 px-4 py-3 text-sm text-brand-900" role="status">
        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="mb-5 flex items-start gap-3 border border-red-200 border-l-4 border-l-red-700 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any() && ! session('error'))
    <div class="mb-5 flex items-start gap-3 border border-red-200 border-l-4 border-l-red-700 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
            <p class="font-medium">Please correct the {{ $errors->count() === 1 ? 'error' : $errors->count().' errors' }} below.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if ($credentials = session('temporary_password'))
    <div class="mb-5 border border-gold-600/40 border-l-4 border-l-gold-600 bg-gold-100 px-4 py-4 text-sm text-ink" role="status">
        <p class="font-semibold">One time password for {{ $credentials['name'] }}</p>
        <p class="mt-1 text-muted">Give these sign in details to the account holder. The password is shown only once and must be changed at first sign in.</p>
        <dl class="mt-3 grid gap-2 sm:grid-cols-2">
            <div><dt class="text-[12px] uppercase tracking-wide text-muted">Email address</dt><dd class="mono">{{ $credentials['email'] }}</dd></div>
            <div><dt class="text-[12px] uppercase tracking-wide text-muted">Password</dt><dd class="mono text-base font-medium">{{ $credentials['password'] }}</dd></div>
        </dl>
    </div>
@endif
