{{-- Minimal layout for documents that are printed, such as the passport card. --}}
@props(['title', 'back' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-paper">
    <div class="no-print border-b border-line bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
            @if ($back)<a href="{{ $back }}" class="link text-sm">Back to the record</a>@else<span></span>@endif
            <button type="button" onclick="window.print()" class="btn-primary btn-sm">Print</button>
        </div>
    </div>
    <main class="mx-auto max-w-4xl px-4 py-8">{{ $slot }}</main>
</body>
</html>
