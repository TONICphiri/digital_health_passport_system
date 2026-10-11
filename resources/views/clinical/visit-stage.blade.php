@php
    use App\Enums\VisitStageType;

    $bp = $vitals->get('bp');
    [$systolic, $diastolic] = $bp ? array_pad(explode('/', (string) $bp->value), 2, null) : [null, null];

    $vitalFields = [
        'temperature' => ['Temperature (°C)', '0.1'],
        'weight' => ['Weight (kg)', '0.1'],
        'height' => ['Height (cm)', '0.1'],
        'pulse_rate' => ['Pulse (per minute)', '1'],
        'systolic_pressure' => ['Systolic pressure (mmHg)', '1'],
        'diastolic_pressure' => ['Diastolic pressure (mmHg)', '1'],
        'oxygen_saturation' => ['Oxygen saturation (%)', '1'],
    ];

    $vitalValues = [
        'temperature' => optional($vitals->get('temperature'))->value,
        'weight' => optional($vitals->get('weight'))->value,
        'height' => optional($vitals->get('height'))->value,
        'pulse_rate' => optional($vitals->get('pulse'))->value,
        'systolic_pressure' => $systolic,
        'diastolic_pressure' => $diastolic,
        'oxygen_saturation' => optional($vitals->get('oxygen'))->value,
    ];
@endphp

<x-layouts.app :title="'Record '.$stage->label()">
    <x-page-header :title="'Record '.$stage->label()">
        <x-slot:breadcrumb>
            <a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            {{ $stage->label() }}
        </x-slot:breadcrumb>
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('visits.stage.store', [$patient, $stage->value]) }}" class="panel lg:col-span-2">
            @csrf
            <div class="panel-body space-y-5">
                @if ($stage === VisitStageType::Reception)
                    <x-field.textarea name="reason" label="Reason for the visit" :value="$stageRecord?->summary ?? $visit->reason" rows="2" required />
                @elseif ($stage === VisitStageType::Triage)
                    <x-field.textarea name="symptoms" label="Symptoms" :value="$stageRecord?->summary" rows="3" required />

                    <div x-data="repeater(@js(old('tests', $stageRecord?->payload['tests'] ?? [])), { name: '', result: '' })">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Tests and results</h3>
                            <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4" /> Add test</button>
                        </div>
                        <p class="mb-2 text-[13px] text-muted">Record each test as a name and a result only.</p>
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="mb-3 grid gap-3 sm:grid-cols-[1.4fr_1fr_auto]">
                                <div><label class="label" :for="'test-name-' + index">Test</label><input :id="'test-name-' + index" class="input" :name="'tests[' + index + '][name]'" x-model="row.name" maxlength="150"></div>
                                <div><label class="label" :for="'test-result-' + index">Result</label><input :id="'test-result-' + index" class="input" :name="'tests[' + index + '][result]'" x-model="row.result" maxlength="255"></div>
                                <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove test"><x-icon name="x" class="h-4 w-4" /></button></div>
                            </div>
                        </template>
                    </div>
                @elseif ($stage === VisitStageType::Consultation)
                    <x-field.input name="diagnosis" label="Diagnosis" :value="$stageRecord?->summary" required />
                    <x-field.textarea name="treatment_summary" label="Treatment given" :value="$stageRecord?->payload['treatment'] ?? null" rows="3" />
                    <x-field.input name="follow_up_on" label="Follow up date" type="date" :value="$visit->follow_up_on?->toDateString()" :min="today()->addDay()->toDateString()" />

                    <div x-data="repeater(@js(old('medications', $visit->exists ? $visit->medications->map(fn ($m) => ['name' => $m->name, 'dosage' => $m->dosage, 'frequency' => $m->frequency, 'duration_days' => $m->duration_days])->all() : [])), { name: '', dosage: '', frequency: '', duration_days: '' }, {{ $maxMedications }})">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Medicines</h3>
                            <button type="button" class="btn-secondary btn-sm" @click="add()" :disabled="rows.length >= {{ $maxMedications }}"><x-icon name="plus" class="h-4 w-4" /> Add medicine</button>
                        </div>
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="mb-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1.2fr_0.8fr_auto]">
                                <div><label class="label" :for="'med-name-' + index">Medicine</label><input :id="'med-name-' + index" class="input" :name="'medications[' + index + '][name]'" x-model="row.name" maxlength="150"></div>
                                <div><label class="label" :for="'med-dose-' + index">Dosage</label><input :id="'med-dose-' + index" class="input" :name="'medications[' + index + '][dosage]'" x-model="row.dosage" maxlength="100"></div>
                                <div>
                                    <label class="label" :for="'med-freq-' + index">How often</label>
                                    <select :id="'med-freq-' + index" class="input" :name="'medications[' + index + '][frequency]'" x-model="row.frequency">
                                        <option value="">Choose</option>
                                        @foreach ($frequencies as $frequency)
                                            <option value="{{ $frequency }}">{{ $frequency }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div><label class="label" :for="'med-days-' + index">Days</label><input :id="'med-days-' + index" type="number" min="1" max="365" class="input" :name="'medications[' + index + '][duration_days]'" x-model="row.duration_days"></div>
                                <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove medicine"><x-icon name="x" class="h-4 w-4" /></button></div>
                            </div>
                        </template>
                    </div>
                @endif

                <div>
                    <h3 class="mb-2 text-sm font-semibold">Vital signs</h3>
                    <p class="mb-3 text-[13px] text-muted">Leave a reading blank if it was not taken. A new reading is added and the earlier ones are kept.</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($vitalFields as $field => [$label, $step])
                            <x-field.input :name="$field" :label="$label" type="number" :value="$vitalValues[$field]" :step="$step" :min="$limits[$field]['min']" :max="$limits[$field]['max']" inputmode="decimal" />
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save {{ $stage->label() }}</button>
            </div>
        </form>

        <section class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">This visit</h2></div>
            @if ($visit->exists)
                <x-visit-timeline :visit="$visit" />
            @else
                <p class="px-5 py-4 text-sm text-muted">No stage has been recorded yet. Reception, triage, consultation and dispensing are shown here as they are recorded.</p>
            @endif
        </section>
    </div>
</x-layouts.app>
