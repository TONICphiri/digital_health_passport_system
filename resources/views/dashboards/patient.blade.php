<x-layouts.app title="My passport">
    <x-page-header :title="'Hello, '.$patient->first_name">
        <x-slot:actions>
            <a href="{{ route('portal.card') }}" class="btn-primary"><x-icon name="qr" class="h-4 w-4" /> My passport card</a>
            <a href="{{ route('portal.records') }}" class="btn-secondary"><x-icon name="heart" class="h-4 w-4" /> Full passport</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Passport</h2></div>
            <dl class="space-y-3 px-5 py-4 text-sm">
                <div><dt class="text-[13px] text-muted">Passport number</dt><dd class="mono font-medium">{{ $patient->passport_number }}</dd></div>
                <div><dt class="text-[13px] text-muted">Blood group</dt><dd class="font-medium">{{ $patient->blood_group ?: 'Not recorded' }}</dd></div>
                <div><dt class="text-[13px] text-muted">Issued at</dt><dd class="font-medium">{{ $patient->registeredFacility?->name }}</dd></div>
            </dl>
        </section>

        <section class="panel lg:col-span-2">
            <div class="panel-header"><h2 class="panel-title">Reminders</h2></div>
            @forelse ($reminders as $reminder)
                <div class="flex items-start justify-between gap-3 border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <div>
                        <p class="font-medium">{{ $reminder->title }}</p>
                        <p class="text-[13px] text-muted">{{ $reminder->patient->full_name }}</p>
                    </div>
                    <span class="text-[13px] text-muted">{{ $reminder->due_on->format('j M Y') }}</span>
                </div>
            @empty
                <x-empty title="No reminders" icon="bell" />
            @endforelse
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Recent encounters</h2></div>
            @forelse ($recentEncounters as $encounter)
                <div class="border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <p class="font-medium">{{ $encounter->diagnosis ?: $encounter->reason }}</p>
                    <p class="text-[13px] text-muted">{{ $encounter->recorded_at->format('j M Y') }}, {{ $encounter->facility->name }}</p>
                </div>
            @empty
                <x-empty title="No encounters yet" icon="stethoscope" />
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Who opened my passport</h2></div>
            @forelse ($recentAccesses as $entry)
                <div class="border-b border-line px-5 py-3 text-sm last:border-b-0">
                    <p class="font-medium">{{ $entry->facility?->name }}</p>
                    <p class="text-[13px] text-muted">{{ $entry->opened_at->format('j M Y, H:i') }}, {{ $entry->method->label() }}</p>
                </div>
            @empty
                <x-empty title="Nobody has opened it yet" icon="shield" />
            @endforelse
        </section>
    </div>
</x-layouts.app>
