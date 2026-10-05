<x-layouts.app title="System settings">
    <x-page-header title="System settings" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @forelse ($groups as $group => $settings)
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">{{ Str::headline($group) }}</h2></div>
                <div class="panel-body grid gap-5 md:grid-cols-2">
                    @foreach ($settings as $setting)
                        @php $field = "settings[{$setting->key}]"; @endphp
                        @switch($setting->input_type)
                            @case('boolean')
                                <x-field.checkbox :name="$field" :label="$setting->label" :checked="$setting->value === '1'" :help="$setting->help_text" class="md:col-span-2" />
                                @break
                            @case('list')
                                <x-field.textarea :name="$field" :label="$setting->label" :value="$setting->value" rows="6" :help="$setting->help_text" required />
                                @break
                            @case('number')
                                <x-field.input :name="$field" :label="$setting->label" type="number" min="0" :value="$setting->value" :help="$setting->help_text" required />
                                @break
                            @case('email')
                                <x-field.input :name="$field" :label="$setting->label" type="email" :value="$setting->value" :help="$setting->help_text" />
                                @break
                            @default
                                <x-field.input :name="$field" :label="$setting->label" :value="$setting->value" :help="$setting->help_text" required />
                        @endswitch
                    @endforeach
                </div>
            </section>
        @empty
            <section class="panel"><x-empty title="No settings found" icon="settings">Run the database seeder to create the default settings.</x-empty></section>
        @endforelse

        <div class="flex justify-end"><button type="submit" class="btn-primary">Save settings</button></div>
    </form>
</x-layouts.app>
