{{-- Cards read without a connection, with forms for the visit notes to send once the server can be reached. --}}
@props(['frequencies', 'limits', 'maxMedications', 'vaccines'])

<div id="pending-scan" hidden class="mt-3 border border-gold-600/30 bg-gold-100 text-sm text-gold-700" role="status">
    <p class="px-3 py-2 font-medium">Waiting for a connection</p>
    <ul id="pending-list" class="border-t border-gold-600/30"></ul>
</div>

<form method="POST" action="{{ route('patients.sync') }}" id="sync-form" class="hidden" aria-hidden="true">
    @csrf
    <input type="hidden" name="code" value="">
    <input type="hidden" name="sync_id" value="">
    <div id="sync-fields"></div>
</form>

<section id="draft-panel" hidden class="panel mt-6" aria-labelledby="draft-title">
    <div class="panel-header">
        <h2 class="panel-title" id="draft-title">Notes for the card read at <span id="draft-time"></span></h2>
        <button type="button" id="draft-close" class="btn-secondary btn-sm">Close</button>
    </div>

    <form id="encounter-draft" data-draft-kind="encounter" hidden class="space-y-5 p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="label" for="d-reason">Reason for the visit</label><input id="d-reason" name="reason" class="input" maxlength="255" required></div>
            <div class="sm:col-span-2"><label class="label" for="d-diagnosis">Diagnosis</label><input id="d-diagnosis" name="diagnosis" class="input" maxlength="255" required></div>
            <div class="sm:col-span-2"><label class="label" for="d-treatment">Treatment given</label><textarea id="d-treatment" name="treatment_summary" class="input" rows="3" maxlength="5000"></textarea></div>
            <div><label class="label" for="d-follow">Follow up date</label><input id="d-follow" name="follow_up_on" type="date" class="input" data-min-days="1"></div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'temperature' => ['Temperature (°C)', '0.1'],
                'weight' => ['Weight (kg)', '0.1'],
                'height' => ['Height (cm)', '0.1'],
                'pulse_rate' => ['Pulse (per minute)', '1'],
                'systolic_pressure' => ['Systolic pressure (mmHg)', '1'],
                'diastolic_pressure' => ['Diastolic pressure (mmHg)', '1'],
                'oxygen_saturation' => ['Oxygen saturation (%)', '1'],
            ] as $field => [$label, $step])
                <div><label class="label" for="d-{{ $field }}">{{ $label }}</label><input id="d-{{ $field }}" name="{{ $field }}" type="number" step="{{ $step }}" min="{{ $limits[$field]['min'] }}" max="{{ $limits[$field]['max'] }}" inputmode="decimal" class="input"></div>
            @endforeach
        </div>

        <div x-data="repeater([], { name: '', dosage: '', frequency: '', duration_days: '' }, {{ $maxMedications }})">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold">Medicines</h3>
                <button type="button" class="btn-secondary btn-sm" @click="add()" :disabled="rows.length >= {{ $maxMedications }}"><x-icon name="plus" class="h-4 w-4" /> Add medicine</button>
            </div>
            <template x-for="(row, index) in rows" :key="index">
                <div class="mb-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1.2fr_0.8fr_auto]">
                    <div><label class="label" :for="'d-med-name-' + index">Medicine</label><input :id="'d-med-name-' + index" class="input" :name="'medications[' + index + '][name]'" x-model="row.name"></div>
                    <div><label class="label" :for="'d-med-dose-' + index">Dosage</label><input :id="'d-med-dose-' + index" class="input" :name="'medications[' + index + '][dosage]'" x-model="row.dosage"></div>
                    <div>
                        <label class="label" :for="'d-med-freq-' + index">How often</label>
                        <select :id="'d-med-freq-' + index" class="input" :name="'medications[' + index + '][frequency]'" x-model="row.frequency">
                            <option value="">Choose</option>
                            @foreach ($frequencies as $frequency)
                                <option value="{{ $frequency }}">{{ $frequency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" :for="'d-med-days-' + index">Days</label><input :id="'d-med-days-' + index" type="number" min="1" max="365" class="input" :name="'medications[' + index + '][duration_days]'" x-model="row.duration_days"></div>
                    <div class="flex items-end"><button type="button" class="btn-secondary btn-sm" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove medicine"><x-icon name="x" class="h-4 w-4" /></button></div>
                </div>
            </template>
        </div>

        <div class="flex justify-end"><button type="submit" class="btn-primary">Keep these notes</button></div>
    </form>

    <form id="vaccination-draft" data-draft-kind="vaccination" hidden class="grid gap-4 p-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="label" for="v-vaccine">Vaccine</label>
            <select id="v-vaccine" name="vaccine_id" class="input" required>
                <option value="">Choose</option>
                @foreach ($vaccines as $vaccine)
                    <option value="{{ $vaccine->id }}">{{ $vaccine->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label" for="v-date">Date given</label><input id="v-date" name="administered_on" type="date" class="input" data-default-today data-max-today required></div>
        <div><label class="label" for="v-batch">Batch number</label><input id="v-batch" name="batch_number" class="input" maxlength="50"></div>
        <div class="sm:col-span-2"><label class="label" for="v-notes">Notes</label><textarea id="v-notes" name="notes" class="input" rows="2" maxlength="500"></textarea></div>
        <div class="flex justify-end sm:col-span-2"><button type="submit" class="btn-primary">Keep this vaccination</button></div>
    </form>
</section>
