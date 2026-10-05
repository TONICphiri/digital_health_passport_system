<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard">
        <x-slot:actions>
            <a href="{{ route('patients.scan') }}" class="btn-primary"><x-icon name="qr" class="h-4 w-4" /> Open a passport</a>
            <a href="{{ route('patients.create') }}" class="btn-secondary"><x-icon name="user-plus" class="h-4 w-4" /> Issue a passport</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Passports opened today" :value="number_format($stats['openedToday'])" icon="qr" />
        <x-stat label="Encounters recorded today" :value="number_format($stats['encountersToday'])" icon="stethoscope" />
        <x-stat label="Passports issued today" :value="number_format($stats['issuedToday'])" icon="user-plus" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Open now</h2></div>
            @forelse ($openPassports as $access)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                    <x-patient-cell :patient="$access->patient" />
                    <span class="text-[13px] text-muted">Until {{ $access->expires_at->format('H:i') }}</span>
                </div>
            @empty
                <x-empty title="No passports open" icon="qr" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">My recent encounters</h2></div>
            @forelse ($recentEncounters as $encounter)
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <div>
                        <p class="font-medium">{{ $encounter->patient->full_name }}</p>
                        <p class="text-[13px] text-muted">{{ $encounter->diagnosis ?: $encounter->reason }}</p>
                    </div>
                    <span class="text-[13px] text-muted">{{ $encounter->recorded_at->diffForHumans() }}</span>
                </div>
            @empty
                <x-empty title="No encounters yet" icon="stethoscope" />
            @endforelse
        </section>
    </div>
</x-layouts.app>
