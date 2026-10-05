@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-[1fr_minmax(0,560px)]">
        <section class="relative hidden flex-col justify-between overflow-hidden bg-brand-900 p-12 text-brand-100 lg:flex">
            <img src="{{ asset('images/slide-banner2.jpg') }}" alt="" loading="lazy"
                class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-brand-950/70"></div>

            <div class="relative flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-lg font-semibold text-white">{{ $systemName }}</p>
                    <p class="text-sm text-brand-200">{{ trim($countryName.', '.$issuingAuthority, ', ') }}</p>
                </div>
            </div>
            <div></div>
            <p class="relative text-[13px] text-brand-200">Unauthorised access to patient information is an offence.</p>
        </section>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
                    <p class="font-semibold">{{ $systemName }}</p>
                </div>
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>