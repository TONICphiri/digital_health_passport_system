<x-layouts.app :title="$patient->full_name">
    <x-page-header :title="$patient->full_name">
        <x-slot:breadcrumb>
            <a href="{{ route('patients.scan') }}" class="hover:text-brand-700">Open a passport</a>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            <span class="mono">{{ $patient->passport_number }}</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            @can('record', $patient)
                <a href="{{ route('encounters.create', $patient) }}" class="btn-primary"><x-icon name="stethoscope" class="h-4 w-4" /> Record encounter</a>
            @endcan
            @can('recordVaccination', $patient)
                <a href="{{ route('vaccinations.create', $patient) }}" class="btn-secondary"><x-icon name="syringe" class="h-4 w-4" /> Vaccination</a>
            @endcan
            @can('manageReminders', $patient)
                <a href="{{ route('reminders.create', $patient) }}" class="btn-secondary"><x-icon name="bell" class="h-4 w-4" /> Reminder</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($access)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border border-brand-200 bg-brand-50 px-5 py-3 text-sm">
            <p><span class="font-medium">{{ $access->method->label() }}.</span> Open until {{ $access->expires_at->format('H:i') }}.</p>
            <form method="POST" action="{{ route('patients.close', $access) }}">@csrf @method('DELETE')<button class="btn-secondary btn-sm">Close passport</button></form>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-3">
        <div class="space-y-6">
            <section class="panel">
                <div class="panel-body flex items-center gap-4">
                    <div class="h-28 w-28 shrink-0 border border-line [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR code of {{ $patient->full_name }}">{!! $qrCode !!}</div>
                    <div class="min-w-0 text-sm">
                        <p class="mono font-medium">{{ $patient->passport_number }}</p>
                        <p class="text-muted">{{ $patient->sex->label() }}, born {{ $patient->date_of_birth->format('j M Y') }}</p>
                        <p class="text-muted">{{ $patient->district?->name }}</p>
                        <x-status :value="$patient->status" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 border-t border-line px-5 py-3">
                    @can('printCard', $patient)<a href="{{ route('patients.card', $patient) }}" class="btn-secondary btn-sm" target="_blank" rel="noopener"><x-icon name="qr" class="h-4 w-4" /> Print card</a>@endcan
                    @can('update', $patient)<a href="{{ route('patients.edit', $patient) }}" class="btn-secondary btn-sm"><x-icon name="edit" class="h-4 w-4" /> Edit details</a>@endcan
                    @can('create', App\Models\Patient::class)
                        <a href="{{ route('patients.create', ['mother' => $patient->id]) }}" class="btn-secondary btn-sm"><x-icon name="user-plus" class="h-4 w-4" /> Add a child</a>
                    @endcan
                </div>
            </section>

            <x-passport-facts :patient="$patient" />

            @if ($patient->mother || $patient->children->isNotEmpty())
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Family</h2></div>
                    <div class="divide-y divide-line">
                        @if ($patient->mother)
                            <div class="px-5 py-3"><p class="text-[13px] text-muted">Mother</p><x-patient-cell :patient="$patient->mother" /></div>
                        @endif
                        @foreach ($patient->children as $child)
                            <div class="px-5 py-3"><p class="text-[13px] text-muted">Child</p><x-patient-cell :patient="$child" /></div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! $patient->portalAccount && $patient->email)
                @can('create', App\Models\Patient::class)
                    <section class="panel">
                        <div class="panel-body flex items-center justify-between gap-3">
                            <p class="text-sm">No portal account</p>
                            <form method="POST" action="{{ route('patients.portal-account', $patient) }}">@csrf<button class="btn-secondary btn-sm">Create account</button></form>
                        </div>
                    </section>
                @endcan
            @endif
        </div>

        <div class="space-y-6 xl:col-span-2">
            <x-encounter-list :encounters="$encounters" />

            <x-vaccination-list :vaccinations="$vaccinations" />

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Reminders</h2></div>
                @forelse ($reminders as $reminder)
                    <div class="flex items-start justify-between gap-3 border-b border-line px-5 py-3 text-sm last:border-b-0">
                        <div>
                            <p class="font-medium">{{ $reminder->title }} <x-badge :tone="$reminder->category->tone()">{{ $reminder->category->label() }}</x-badge></p>
                            <p class="text-[13px] text-muted">Due {{ $reminder->due_on->format('j M Y') }}@if ($reminder->repeat_every_days), every {{ $reminder->repeat_every_days }} days @endif</p>
                            @php $deliveries = $reminder->lastDeliveries(); @endphp
                            @if ($deliveries->isNotEmpty())
                                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-[13px] text-muted">
                                    Last sent {{ $reminder->last_sent_at?->format('j M Y') }}:
                                    @foreach ($deliveries as $delivery)
                                        <x-badge :tone="$delivery->tone()" title="{{ $delivery->detail }}">{{ $delivery->channelLabel() }} {{ $delivery->status }}</x-badge>
                                    @endforeach
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status :value="$reminder->status" />
                            @can('manageReminders', $patient)
                                @if ($reminder->status === App\Enums\ReminderStatus::Active)
                                    <form method="POST" action="{{ route('reminders.stop', $reminder) }}">@csrf @method('PATCH')<button class="btn-secondary btn-sm">Stop</button></form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @empty
                    <x-empty title="No reminders" icon="bell" />
                @endforelse
            </section>
        </div>
    </div>
</x-layouts.app>
