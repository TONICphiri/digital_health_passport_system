<x-layouts.bare title="Open a passport">
    @push('head') @vite('resources/js/scanner.js') @endpush
    <h1 class="mb-6 text-2xl">Open a passport</h1>

    <form method="POST" action="{{ route('patients.open') }}" id="scan-form" class="panel"
        data-scan-url="{{ route('patients.scan') }}" data-worker="{{ asset('sw.js') }}" data-shell="{{ route('offline.scan') }}"
        data-keep-minutes="{{ $keepMinutes }}" data-draft-hours="{{ $draftHours }}" data-user-id="">
        <input type="hidden" name="_token" value="">
        <input type="hidden" name="code" id="code">
        <div class="panel-header"><h2 class="panel-title">Scan the card</h2></div>
        <div class="panel-body">
            <div id="qr-reader" class="aspect-square w-full max-w-sm border border-line bg-paper"></div>
            <p id="scanner-status" class="mt-3 text-sm text-muted" aria-live="polite"></p>
            <div class="mt-3"><button type="button" id="start-scan" class="btn-primary"><x-icon name="qr" class="h-4 w-4" /> Start camera</button></div>
        </div>
    </form>
    <x-offline-queue :frequencies="$frequencies" :limits="$limits" :max-medications="$maxMedications" :vaccines="$vaccines" />
</x-layouts.bare>
