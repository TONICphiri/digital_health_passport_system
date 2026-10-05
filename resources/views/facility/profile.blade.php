<x-layouts.app title="Facility profile">
    <x-page-header :title="$facility->name" />
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('facility.profile.update') }}" class="panel lg:col-span-2">
            @csrf @method('PUT')
            <div class="panel-header"><h2 class="panel-title">Contact details</h2></div>
            <div class="panel-body grid gap-5 sm:grid-cols-2">
                <x-field.input name="physical_address" label="Physical address" :value="$facility->physical_address" class="sm:col-span-2" />
                <x-field.input name="phone" label="Phone number" :value="$facility->phone" />
                <x-field.input name="email" label="Email address" type="email" :value="$facility->email" />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-3"><button type="submit" class="btn-primary">Save changes</button></div>
        </form>
        <section class="panel self-start">
            <div class="panel-header"><h2 class="panel-title">Registration</h2></div>
            <dl class="panel-body detail-list !grid-cols-1">
                <div><dt>Facility code</dt><dd class="mono">{{ $facility->code }}</dd></div>
                <div><dt>Type</dt><dd>{{ $facility->type }}</dd></div>
                <div><dt>Ownership</dt><dd>{{ $facility->ownership }}</dd></div>
                <div><dt>District</dt><dd>{{ $facility->district->name }}, {{ $facility->district->region }} Region</dd></div>
            </dl>
        </section>
    </div>
</x-layouts.app>
