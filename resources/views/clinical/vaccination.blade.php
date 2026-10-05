<x-layouts.app title="Record vaccination">
    <x-page-header :title="'Record a vaccination for '.$patient->full_name">
        <x-slot:breadcrumb><a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Vaccination</x-slot:breadcrumb>
    </x-page-header>
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('vaccinations.store', $patient) }}" class="panel lg:col-span-2">
            @csrf
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.select name="vaccine_id" label="Vaccine" class="sm:col-span-2" required
                    :options="$vaccines->mapWithKeys(fn ($vaccine) => [$vaccine->id => $vaccine->name.' ('.$vaccine->protects_against.', '.$vaccine->total_doses.' '.\Illuminate\Support\Str::plural('dose', $vaccine->total_doses).')'])->all()" />
                <x-field.input name="administered_on" label="Date given" type="date" :value="today()->toDateString()" :max="today()->toDateString()" required />
                <x-field.input name="batch_number" label="Batch number" />
                <x-field.textarea name="notes" label="Notes" rows="2" class="sm:col-span-2" placeholder="Reactions or advice given" />
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Record vaccination</button>
            </div>
        </form>
        <section class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">Already given</h2></div>
            @forelse ($given as $vaccination)
                <div class="border-b border-line px-5 py-3 last:border-b-0">
                    <p class="font-medium">{{ $vaccination->vaccine->name }}, dose {{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</p>
                    <p class="text-[13px] text-muted">{{ $vaccination->administered_on->format('j M Y') }}{{ $vaccination->next_dose_due_on ? '. Next dose due '.$vaccination->next_dose_due_on->format('j M Y') : '' }}</p>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-muted">No vaccinations recorded yet.</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
