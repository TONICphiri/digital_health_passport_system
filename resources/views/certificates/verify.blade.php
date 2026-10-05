<x-layouts.guest title="Certificate check">
    <h1 class="text-xl">Genuine certificate</h1>
    <dl class="mt-6 space-y-3 border border-line bg-white p-5 text-sm">
        <div><dt class="text-[13px] text-muted">Holder</dt><dd class="font-medium">{{ $vaccination->patient->first_name }}</dd></div>
        <div><dt class="text-[13px] text-muted">Vaccine</dt><dd class="font-medium">{{ $vaccination->vaccine->name }}</dd></div>
        <div><dt class="text-[13px] text-muted">Dose</dt><dd class="font-medium">{{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</dd></div>
        <div><dt class="text-[13px] text-muted">Date given</dt><dd class="font-medium">{{ $vaccination->administered_on->format('j M Y') }}</dd></div>
        <div><dt class="text-[13px] text-muted">Given at</dt><dd class="font-medium">{{ $vaccination->facility->name }}</dd></div>
    </dl>
</x-layouts.guest>
