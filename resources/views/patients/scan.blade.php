<x-layouts.app title="Open a passport">
    @push('head') @vite('resources/js/scanner.js') @endpush
    <x-page-header title="Open a passport">
        <x-slot:actions>
            <a href="{{ route('patients.create') }}" class="btn-secondary"><x-icon name="user-plus" class="h-4 w-4" /> Issue a passport</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-2" x-data="{ noCard: {{ $errors->hasAny(['identifier', 'date_of_birth']) ? 'true' : 'false' }} }">
        <div>
        <form method="POST" action="{{ route('patients.open') }}" id="scan-form" class="panel"
            data-scan-url="{{ route('patients.scan') }}" data-worker="{{ asset('sw.js') }}" data-shell="{{ route('offline.scan') }}" data-keep-minutes="{{ $keepMinutes }}" data-draft-hours="{{ $draftHours }}" data-user-id="{{ auth()->id() }}">
            @csrf
            <input type="hidden" name="code" id="code">
            <div class="panel-header"><h2 class="panel-title">Scan the card</h2></div>
            <div class="panel-body">
                <div id="qr-reader" class="aspect-square w-full max-w-sm border border-line bg-paper"></div>
                <p id="scanner-status" class="mt-3 text-sm text-muted" aria-live="polite"></p>
                @error('code')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" id="start-scan" class="btn-primary"><x-icon name="qr" class="h-4 w-4" /> Start camera</button>
                    <button type="button" class="btn-secondary" x-on:click="noCard = !noCard" x-bind:aria-expanded="noCard">Holder has no card</button>
                </div>
            </div>
        </form>
        <x-offline-queue :frequencies="$frequencies" :limits="$limits" :max-medications="$maxMedications" :vaccines="$vaccines" />
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('patients.open') }}" class="panel" x-show="noCard" x-cloak>
                @csrf
                <div class="panel-header"><h2 class="panel-title">Check the holder's details</h2></div>
                <div class="panel-body grid gap-4">
                    <x-field.input name="identifier" label="Passport number or National ID" autocomplete="off" required />
                    <x-field.input name="date_of_birth" label="Date of birth" type="date" :max="today()->toDateString()" required />
                </div>
                <div class="border-t border-line px-5 py-3"><button type="submit" class="btn-primary">Open passport</button></div>
            </form>

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Open now</h2></div>
                @forelse ($openPassports as $access)
                    <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                        <x-patient-cell :patient="$access->patient" />
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] text-muted">Until {{ $access->expires_at->format('H:i') }}</span>
                            <form method="POST" action="{{ route('patients.close', $access) }}">@csrf @method('DELETE')<button class="btn-secondary btn-sm">Close</button></form>
                        </div>
                    </div>
                @empty
                    <x-empty title="No passports open" icon="qr" />
                @endforelse
            </section>
        </div>
    </div>
</x-layouts.app>
