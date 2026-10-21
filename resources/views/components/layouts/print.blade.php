{{-- Minimal layout for documents that are printed, such as the passport card. --}}
@props(['title', 'back' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#1e3a8a">
    @vite(['resources/css/app.css'])
    {{-- Zero page margins so the browser has no room to stamp its own
         headers/footers (date, page title, URL) around the document. --}}
    <style>@page { margin: 0; }</style>
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
