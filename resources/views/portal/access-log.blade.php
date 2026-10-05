@php $viewingChild = $patient->id !== $owner->id; @endphp
<x-layouts.app title="Who opened the passport">
    <x-page-header :title="$viewingChild ? 'Who opened the passport of '.$patient->full_name : 'Who opened my passport'">
        <x-slot:breadcrumb><a href="{{ route('portal.records', $viewingChild ? ['patient' => $patient->id] : []) }}" class="link">My passport</a></x-slot:breadcrumb>
    </x-page-header>

    @if ($family->count() > 1)
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Family members">
            @foreach ($family as $member)
                <a href="{{ route('portal.access-log', $member->id === $owner->id ? [] : ['patient' => $member->id]) }}"
                    @if ($member->id === $patient->id) aria-current="page" @endif
                    class="border px-3 py-2 text-sm {{ $member->id === $patient->id ? 'border-brand-700 bg-brand-700 text-white' : 'border-line bg-white hover:border-brand-600' }}">
                    {{ $member->id === $owner->id ? 'Me' : $member->first_name.', '.$member->age_label }}
                </a>
            @endforeach
        </nav>
    @endif

    <section class="panel">
        @if ($accesses->isEmpty())
            <x-empty title="Nobody has opened it yet" icon="shield" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Opened</th><th>Facility</th><th>Health worker</th><th>How</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($accesses as $entry)
                            <tr>
                                <td class="whitespace-nowrap">{{ $entry->opened_at->format('j M Y, H:i') }}</td>
                                <td class="font-medium">{{ $entry->facility?->name ?? 'Removed facility' }}</td>
                                <td>{{ $entry->user?->name ?? 'Removed account' }}</td>
                                <td>{{ $entry->method->label() }}</td>
                                <td>
                                    @if ($entry->isOpen())
                                        <x-badge tone="warning">Open until {{ $entry->expires_at->format('H:i') }}</x-badge>
                                    @else
                                        <x-badge tone="neutral">Closed</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $accesses->links() }}
        @endif
    </section>
</x-layouts.app>
