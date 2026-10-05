@php $viewingChild = $patient->id !== $owner->id; @endphp
<x-layouts.app title="My passport">
    <x-page-header :title="$viewingChild ? 'Passport of '.$patient->full_name : 'My passport'">
        <x-slot:breadcrumb><span class="mono">{{ $patient->passport_number }}</span></x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('portal.summary', $viewingChild ? ['patient' => $patient->id] : []) }}" class="btn-secondary" target="_blank" rel="noopener"><x-icon name="printer" class="h-4 w-4" /> Print summary</a>
            <a href="{{ route('portal.card', $viewingChild ? ['patient' => $patient->id] : []) }}" class="btn-primary" target="_blank" rel="noopener"><x-icon name="qr" class="h-4 w-4" /> Passport card</a>
        </x-slot:actions>
    </x-page-header>

    @if ($family->count() > 1)
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Family members">
            @foreach ($family as $member)
                <a href="{{ route('portal.records', $member->id === $owner->id ? [] : ['patient' => $member->id]) }}"
                    @if ($member->id === $patient->id) aria-current="page" @endif
                    class="border px-3 py-2 text-sm {{ $member->id === $patient->id ? 'border-brand-700 bg-brand-700 text-white' : 'border-line bg-white hover:border-brand-600' }}">
                    {{ $member->id === $owner->id ? 'Me' : $member->first_name.', '.$member->age_label }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-encounter-list :encounters="$encounters" />
            <x-vaccination-list :vaccinations="$vaccinations" />
        </div>

        <aside class="space-y-6">
            <x-passport-facts :patient="$patient" />

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Upcoming reminders</h2></div>
                @forelse ($reminders as $reminder)
                    <div class="border-b border-line px-5 py-3 text-sm last:border-b-0">
                        <p class="font-medium">{{ $reminder->title }}</p>
                        <p class="text-muted">{{ $reminder->message }}</p>
                        <p class="mt-1 text-[13px] {{ $reminder->due_on->isPast() && ! $reminder->due_on->isToday() ? 'font-medium text-red-700' : 'text-muted' }}">{{ $reminder->due_on->isPast() && ! $reminder->due_on->isToday() ? 'Overdue since' : 'Due' }} {{ $reminder->due_on->format('j M Y') }}</p>
                    </div>
                @empty
                    <x-empty title="No reminders" icon="bell" />
                @endforelse
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Who opened this passport</h2>
                    <a href="{{ route('portal.access-log', $viewingChild ? ['patient' => $patient->id] : []) }}" class="link text-[13px]">View all</a>
                </div>
                @forelse ($accesses as $entry)
                    <div class="border-b border-line px-5 py-3 text-sm last:border-b-0">
                        <p class="font-medium">{{ $entry->facility?->name }}</p>
                        <p class="text-[13px] text-muted">{{ $entry->user?->name }}, {{ $entry->opened_at->format('j M Y, H:i') }}</p>
                        <p class="text-[13px] text-muted">{{ $entry->method->label() }}</p>
                    </div>
                @empty
                    <x-empty title="Nobody has opened it yet" icon="shield" />
                @endforelse
            </section>
        </aside>
    </div>
</x-layouts.app>
