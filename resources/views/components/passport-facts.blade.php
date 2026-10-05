{{-- Identity and the essential health facts that make up the front page of a passport. --}}
@props(['patient'])
<section class="panel">
    <div class="panel-header"><h2 class="panel-title">Health facts</h2></div>
    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 px-5 py-4 text-sm">
        <div><dt class="text-[13px] text-muted">Blood group</dt><dd class="font-medium">{{ $patient->blood_group ?: 'Not recorded' }}</dd></div>
        <div><dt class="text-[13px] text-muted">Age</dt><dd class="font-medium">{{ $patient->age_label }}</dd></div>
        <div class="col-span-2"><dt class="text-[13px] text-muted">Allergies</dt><dd class="font-medium">{{ $patient->allergies ?: 'None recorded' }}</dd></div>
        <div class="col-span-2"><dt class="text-[13px] text-muted">Long term conditions</dt><dd class="font-medium">{{ $patient->chronic_conditions ?: 'None recorded' }}</dd></div>
        @if ($patient->disabilities)
            <div class="col-span-2"><dt class="text-[13px] text-muted">Disabilities</dt><dd class="font-medium">{{ $patient->disabilities }}</dd></div>
        @endif
    </dl>
</section>

<section class="panel">
    <div class="panel-header"><h2 class="panel-title">Emergency contacts</h2></div>
    @forelse ($patient->emergencyContacts as $contact)
        <div class="border-b border-line px-5 py-3 text-sm last:border-b-0">
            <p class="font-medium">{{ $contact->full_name }}@if ($contact->relationship) <span class="font-normal text-muted">, {{ $contact->relationship }}</span>@endif</p>
            <p class="mono text-muted">{{ $contact->phone }}</p>
        </div>
    @empty
        <p class="px-5 py-4 text-sm text-muted">No contacts recorded.</p>
    @endforelse
</section>
