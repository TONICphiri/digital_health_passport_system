{{-- Plain layout for the page that is kept on the device. It shows no account or patient information. --}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
    <header class="border-b border-line bg-brand-900 text-white">
        <div class="mx-auto flex max-w-4xl items-center gap-3 px-4 py-3">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
            <p class="font-semibold">{{ $systemName }}</p>
        </div>
    </header>
    <main class="mx-auto max-w-4xl px-4 py-6">{{ $slot }}</main>
</body>
</html>
