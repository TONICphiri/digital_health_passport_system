<x-layouts.app title="Record encounter">
    <x-page-header :title="'Record an encounter for '.$patient->full_name">
        <x-slot:breadcrumb><a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Encounter</x-slot:breadcrumb>
    </x-page-header>

    <form method="POST" action="{{ route('encounters.store', $patient) }}" class="space-y-6">
        @csrf

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Visit</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.input name="reason" label="Reason for the visit" required class="sm:col-span-2" />
                <x-field.input name="diagnosis" label="Diagnosis" required class="sm:col-span-2" />
                <x-field.textarea name="treatment_summary" label="Treatment given" rows="3" class="sm:col-span-2" />
                <x-field.input name="follow_up_on" label="Follow up date" type="date" :min="today()->addDay()->toDateString()" />
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Measurements</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'temperature' => ['Temperature (°C)', '0.1'],
                    'weight' => ['Weight (kg)', '0.1'],
                    'height' => ['Height (cm)', '0.1'],
                    'pulse_rate' => ['Pulse (per minute)', '1'],
                    'systolic_pressure' => ['Systolic pressure (mmHg)', '1'],
                    'diastolic_pressure' => ['Diastolic pressure (mmHg)', '1'],
                    'oxygen_saturation' => ['Oxygen saturation (%)', '1'],
                ] as $field => [$label, $step])
                    <x-field.input :name="$field" :label="$label" type="number" :step="$step" :min="$limits[$field]['min']" :max="$limits[$field]['max']" inputmode="decimal" />
                @endforeach
            </div>
        </section>

        <section class="panel" x-data="repeater(@js(old('medications', [])), { name: '', dosage: '', frequency: '', duration_days: '' }, {{ $maxMedications }})">
            <div class="panel-header">
                <h2 class="panel-title">Medicines</h2>
                <button type="button" class="btn-secondary btn-sm" @click="add()" :disabled="rows.length >= {{ $maxMedications }}"><x-icon name="plus" class="h-4 w-4" /> Add medicine</button>
            </div>
            <template x-for="(row, index) in rows" :key="index">
                <div class="grid gap-4 border-b border-line px-5 py-4 last:border-b-0 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1.2fr_0.8fr_auto]">
                    <div><label class="label" :for="'medication_name_' + index">Medicine</label><input :id="'medication_name_' + index" class="input" :name="'medications[' + index + '][name]'" x-model="row.name"></div>
                    <div><label class="label" :for="'medication_dosage_' + index">Dosage</label><input :id="'medication_dosage_' + index" class="input" :name="'medications[' + index + '][dosage]'" x-model="row.dosage"></div>
                    <div>
                        <label class="label" :for="'medication_frequency_' + index">How often</label>
                        <select :id="'medication_frequency_' + index" class="input" :name="'medications[' + index + '][frequency]'" x-model="row.frequency">
                            <option value="">Choose</option>
                            @foreach ($frequencies as $frequency)
                                <option value="{{ $frequency }}">{{ $frequency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" :for="'medication_days_' + index">Days</label><input :id="'medication_days_' + index" type="number" min="1" max="365" class="input" :name="'medications[' + index + '][duration_days]'" x-model="row.duration_days"></div>
                    <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove medicine"><x-icon name="x" class="h-4 w-4" /></button></div>
                </div>
            </template>
            @if ($errors->has('medications.*') || $errors->has('medications'))
                <p class="field-error px-5 pb-3">{{ collect($errors->get('medications.*'))->flatten()->first() ?? $errors->first('medications') }}</p>
            @endif
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save encounter</button>
        </div>
    </form>
</x-layouts.app>
