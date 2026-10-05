@php $viewingOther = $patient->id !== auth()->user()->patient_id; @endphp
<x-layouts.print title="Passport summary" :back="route('portal.records', $viewingOther ? ['patient' => $patient->id] : [])">
    <article class="mx-auto max-w-3xl border border-ink bg-white" aria-label="Passport summary">
        <header class="flex items-center gap-3 bg-brand-900 px-6 py-4 text-white">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-wider text-brand-200">{{ $countryName }}, {{ $issuingAuthority }}</p>
                <p class="text-lg font-semibold">{{ $systemName }}: summary</p>
            </div>
        </header>

        <div class="space-y-6 p-6 text-sm">
            <section>
                <h2 class="text-base">{{ $patient->full_name }}</h2>
                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
                    <div><dt class="text-[13px] text-muted">Passport number</dt><dd class="mono font-medium">{{ $patient->passport_number }}</dd></div>
                    <div><dt class="text-[13px] text-muted">National ID</dt><dd class="mono font-medium">{{ $patient->national_id ?? 'Not issued' }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Date of birth</dt><dd class="font-medium">{{ $patient->date_of_birth->format('j M Y') }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Sex</dt><dd class="font-medium">{{ $patient->sex->label() }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Blood group</dt><dd class="font-medium">{{ $patient->blood_group ?: 'Not recorded' }}</dd></div>
                    <div class="col-span-2 sm:col-span-3"><dt class="text-[13px] text-muted">Allergies</dt><dd class="font-medium">{{ $patient->allergies ?: 'None recorded' }}</dd></div>
                    <div class="col-span-2 sm:col-span-4"><dt class="text-[13px] text-muted">Long term conditions</dt><dd class="font-medium">{{ $patient->chronic_conditions ?: 'None recorded' }}</dd></div>
                </dl>
            </section>

            <section>
                <h2 class="border-b border-line pb-1 text-base">Emergency contacts</h2>
                @forelse ($patient->emergencyContacts as $contact)
                    <p class="mt-2">{{ $contact->full_name }}@if ($contact->relationship), {{ $contact->relationship }}@endif, <span class="mono">{{ $contact->phone }}</span></p>
                @empty
                    <p class="mt-2 text-muted">No contacts recorded.</p>
                @endforelse
            </section>

            <section>
                <h2 class="border-b border-line pb-1 text-base">Vaccinations</h2>
                @if ($vaccinations->isEmpty())
                    <p class="mt-2 text-muted">No vaccinations recorded.</p>
                @else
                    <table class="table mt-2">
                        <thead><tr><th>Vaccine</th><th>Dose</th><th>Given</th><th>Next dose</th><th>Facility</th></tr></thead>
                        <tbody>
                            @foreach ($vaccinations as $vaccination)
                                <tr>
                                    <td class="font-medium">{{ $vaccination->vaccine->name }}</td>
                                    <td class="tabular-nums">{{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</td>
                                    <td>{{ $vaccination->administered_on->format('j M Y') }}</td>
                                    <td>{{ $vaccination->next_dose_due_on?->format('j M Y') ?? 'Complete' }}</td>
                                    <td>{{ $vaccination->facility->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>

        <footer class="border-t border-line px-6 py-3 text-[12px] text-muted">Printed {{ now()->format('j M Y') }}. Issued by {{ $patient->registeredFacility?->name }}.</footer>
    </article>
</x-layouts.print>
