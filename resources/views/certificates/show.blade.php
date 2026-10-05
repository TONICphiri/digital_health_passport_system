<x-layouts.print title="Vaccination certificate" :back="$back">
    <article class="mx-auto max-w-2xl border border-ink bg-white" aria-label="Vaccination certificate">
        <header class="flex items-center gap-3 bg-brand-900 px-6 py-4 text-white">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-wider text-brand-200">{{ $countryName }}, {{ $issuingAuthority }}</p>
                <p class="text-lg font-semibold">Vaccination certificate</p>
            </div>
        </header>

        <div class="grid gap-6 p-6 sm:grid-cols-[1fr_auto]">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-[13px] text-muted">Name</dt><dd class="text-base font-semibold">{{ $patient->full_name }}</dd></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><dt class="text-[13px] text-muted">Date of birth</dt><dd class="font-medium">{{ $patient->date_of_birth->format('j M Y') }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Passport number</dt><dd class="mono font-medium">{{ $patient->passport_number }}</dd></div>
                </div>
                <div class="border-t border-line pt-3"><dt class="text-[13px] text-muted">Vaccine</dt><dd class="text-base font-semibold">{{ $vaccination->vaccine->name }}</dd></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><dt class="text-[13px] text-muted">Dose</dt><dd class="font-medium">{{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Date given</dt><dd class="font-medium">{{ $vaccination->administered_on->format('j M Y') }}</dd></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><dt class="text-[13px] text-muted">Batch number</dt><dd class="mono font-medium">{{ $vaccination->batch_number ?: 'Not recorded' }}</dd></div>
                    <div><dt class="text-[13px] text-muted">Next dose</dt><dd class="font-medium">{{ $vaccination->next_dose_due_on?->format('j M Y') ?? 'Course complete' }}</dd></div>
                </div>
                <div><dt class="text-[13px] text-muted">Given at</dt><dd class="font-medium">{{ $vaccination->facility->name }}</dd></div>
            </dl>

            <div class="flex flex-col items-center gap-2">
                <div class="h-32 w-32 [&>svg]:h-full [&>svg]:w-full">{!! $qrCode !!}</div>
                <p class="max-w-[8rem] text-center text-[12px] text-muted">Scan to check this certificate.</p>
            </div>
        </div>

        <footer class="border-t border-line px-6 py-3 text-[12px] text-muted">Printed {{ now()->format('j M Y') }}</footer>
    </article>
</x-layouts.print>
