<x-layouts.app title="Reminder preferences">
    <x-page-header title="Reminder preferences" />

    <form method="POST" action="{{ route('portal.preferences.update') }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">How should reminders reach me</h2></div>
            <div class="panel-body space-y-4">
                <p class="text-sm text-muted">Reminders always appear in your portal.</p>
                <x-field.checkbox name="email" label="Email" :checked="$email" :help="$user->email.($emailAvailable ? '' : ' (not switched on for this system yet)')" />
                <x-field.checkbox name="sms" label="Text message" :checked="$sms" :help="($user->phone ?: 'No phone number on your profile').($smsAvailable ? '' : ' (not switched on for this system yet)')" />
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Which reminders do I want</h2></div>
            <div class="panel-body space-y-4">
                @foreach ($categories as $key => $category)
                    <x-field.checkbox :name="'categories['.$key.']'" :label="$category['label']" :checked="$category['wanted']" />
                @endforeach
            </div>
        </section>

        <div><button type="submit" class="btn-primary">Save preferences</button></div>
    </form>
</x-layouts.app>
