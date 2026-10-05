{{-- Name and passport number of a patient, linked to the record when the user may open it. --}}
@props(['patient', 'link' => true])
<div class="min-w-0">
    @if ($link && auth()->user()->can('view', $patient))
        <a href="{{ route('patients.show', $patient) }}" class="font-medium text-ink hover:text-brand-700 hover:underline">{{ $patient->full_name }}</a>
    @else
        <span class="font-medium text-ink">{{ $patient->full_name }}</span>
    @endif
    <p class="mono text-muted">{{ $patient->passport_number }}</p>
</div>
