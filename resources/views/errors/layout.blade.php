{{-- Standalone error page. It does not depend on the signed in user or the database. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ $systemName ?? config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-paper p-6 font-sans text-ink">
    <main class="w-full max-w-lg border border-line bg-white">
        <div class="flex items-center gap-3 border-b border-line px-6 py-4">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
            <p class="text-sm font-semibold">{{ $systemName ?? config('app.name') }}</p>
        </div>
        <div class="px-6 py-8">
            <p class="font-mono text-sm text-muted">Error @yield('code')</p>
            <h1 class="mt-1 text-xl font-semibold">@yield('title')</h1>
            <p class="mt-3 text-sm text-muted">@yield('message')</p>
            @yield('extra')
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ url('/dashboard') }}" class="btn-primary">Go to the dashboard</a>
                <a href="javascript:history.back()" class="btn-secondary">Go back</a>
            </div>
        </div>
    </main>
</body>
</html>
