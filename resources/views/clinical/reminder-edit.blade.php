<x-layouts.app title="Edit reminder">
    <x-page-header :title="'Edit reminder for '.$patient->full_name">
        <x-slot:breadcrumb><a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Edit Reminder</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ route('reminders.update', $reminder) }}" class="panel max-w-3xl">
        @csrf
        @method('PUT')
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.select name="category" label="Type" :options="$categories" :value="old('category', $reminder->category?->value)" required />
            <x-field.input name="title" label="Title" required :value="old('title', $reminder->title)" placeholder="For example, Collect monthly medication" />
            <x-field.textarea name="message" label="Message to the patient" rows="3" class="sm:col-span-2" required>{{ old('message', $reminder->message) }}</x-field.textarea>
            <x-field.input name="due_on" label="First reminder date" type="date" :min="today()->toDateString()" :value="old('due_on', $reminder->due_on?->toDateString())" required />
            <x-field.input name="repeat_every_days" label="Repeat every (days)" type="number" min="1" max="365" :value="old('repeat_every_days', $reminder->repeat_every_days)" />
            <x-field.checkbox name="is_confidential" label="Confidential" :checked="old('is_confidential', $reminder->is_confidential)" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save reminder</button>
        </div>
    </form>
</x-layouts.app>
