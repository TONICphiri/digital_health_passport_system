<x-layouts.app title="Hand over medicines">
    <x-page-header title="Hand over medicines">
        <x-slot:breadcrumb>
            <a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            Dispensing
        </x-slot:breadcrumb>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('visits.dispense.store', [$patient, $visit]) }}" class="panel lg:col-span-2">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Prescribed medicines</h2></div>

            @if ($visit->medications->isEmpty())
                <x-empty title="No medicines were prescribed" icon="pill" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($visit->medications as $medication)
                        <label class="flex items-center gap-3 px-5 py-3 {{ $medication->dispensed ? 'opacity-60' : 'cursor-pointer' }}">
                            @if ($medication->dispensed)
                                <span class="flex h-5 w-5 items-center justify-center text-brand-700"><x-icon name="check" class="h-4 w-4" /></span>
                            @else
                                <input type="checkbox" name="medications[]" value="{{ $medication->id }}" class="h-5 w-5" checked>
                            @endif
                            <span class="min-w-0 text-sm">
                                <span class="font-medium">{{ $medication->name }}</span>
                                <span class="text-muted">{{ $medication->directions() }}</span>
                                <span class="block text-[13px] text-muted">Prescribed by {{ $medication->prescribedBy?->name ?? 'an unknown user' }}@if ($medication->dispensed), handed over{{ $medication->dispensedBy ? ' by '.$medication->dispensedBy->name : '' }}@endif</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            @error('medications')<p class="field-error px-5 py-2">{{ $message }}</p>@enderror

            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" @disabled($visit->medications->where('dispensed', false)->isEmpty())>Hand over the chosen medicines</button>
            </div>
        </form>

        <section class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">This visit</h2></div>
            <x-visit-timeline :visit="$visit" />
        </section>
    </div>
</x-layouts.app>
