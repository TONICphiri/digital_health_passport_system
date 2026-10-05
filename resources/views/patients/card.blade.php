@php $contact = $patient->emergencyContacts->first(); @endphp
<x-layouts.print title="Health passport card" :back="auth()->user()->can('view', $patient) ? route('patients.show', $patient) : route('portal.records')">
    <div class="flex flex-wrap gap-6">
        {{-- Front --}}
        <article class="flex h-[54mm] w-[85.6mm] flex-col border border-ink bg-white" aria-label="Front of card">
            <header class="flex items-center gap-2 bg-brand-900 px-3 py-1.5 text-white">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-6 w-6 object-contain">
                <div class="leading-tight">
                    <p class="text-[9px] uppercase tracking-wider text-brand-200">{{ $countryName }}</p>
                    <p class="text-[11px] font-semibold">{{ $systemName }}</p>
                </div>
            </header>
            <div class="flex flex-1 gap-3 p-3">
                <div class="min-w-0 flex-1 space-y-1 text-[10px] leading-tight">
                    <p class="text-[12px] font-semibold text-ink">{{ $patient->full_name }}</p>
                    <p><span class="text-muted">Passport</span> <span class="mono text-[11px] font-semibold">{{ $patient->passport_number }}</span></p>
                    <p><span class="text-muted">National ID</span> <span class="mono">{{ $patient->national_id ?? 'Not issued' }}</span></p>
                    <p><span class="text-muted">Born</span> {{ $patient->date_of_birth->format('j M Y') }}, {{ $patient->sex->label() }}</p>
                    <p><span class="text-muted">Blood group</span> {{ $patient->blood_group ?? 'Not known' }}</p>
                </div>
                <div class="h-[30mm] w-[30mm] shrink-0 [&>svg]:h-full [&>svg]:w-full">{!! $qrCode !!}</div>
            </div>
        </article>

        {{-- Back --}}
        <article class="flex h-[54mm] w-[85.6mm] flex-col border border-ink bg-white p-3 text-[10px] leading-snug" aria-label="Back of card">
            <p class="text-[9px] font-semibold uppercase tracking-wider text-muted">Allergies</p>
            <p class="font-medium text-red-700">{{ $patient->allergies ?? 'None recorded' }}</p>
            <p class="mt-2 text-[9px] font-semibold uppercase tracking-wider text-muted">Emergency contact</p>
            <p>{{ $contact ? $contact->full_name.' ('.$contact->relationship.'), '.$contact->phone : 'Not recorded' }}</p>
            <p class="mt-2 text-[9px] font-semibold uppercase tracking-wider text-muted">Issued by</p>
            <p>{{ $patient->registeredFacility?->name }}</p>
            <p class="mt-auto border-t border-line pt-1.5 text-[9px] text-muted">If found, return to any health facility.</p>
        </article>
    </div>
</x-layouts.print>
