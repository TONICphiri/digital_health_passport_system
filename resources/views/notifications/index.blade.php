<x-layouts.app title="Notifications">
    <x-page-header title="Notifications">
        <x-slot:actions>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit" class="btn-secondary"><x-icon name="check" class="h-4 w-4" /> Mark all as read</button></form>
            @endif
        </x-slot:actions>
    </x-page-header>
    <section class="panel">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <a href="{{ route('notifications.read', $notification->id) }}" class="flex items-start gap-4 border-b border-line px-5 py-4 last:border-b-0 hover:bg-paper {{ $notification->read_at ? '' : 'bg-brand-50/60' }}">
                <span class="mt-1.5 h-2 w-2 shrink-0 {{ $notification->read_at ? 'bg-transparent' : 'bg-brand-600' }}" aria-hidden="true"></span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-medium text-ink">{{ $data['title'] ?? 'Notification' }}</span>
                        <span class="text-[12px] text-muted">{{ $notification->created_at->format('j M Y H:i') }}</span>
                    </span>
                    <span class="mt-0.5 block text-sm text-muted">{{ $data['message'] ?? '' }}</span>
                    @if (! empty($data['category']))<span class="mt-1 inline-block text-[12px] uppercase tracking-wide text-muted">{{ str_replace('_', ' ', $data['category']) }}</span>@endif
                </span>
                @unless ($notification->read_at)<span class="sr-only">Unread</span>@endunless
            </a>
        @empty
            <x-empty title="You have no notifications" icon="bell" />
        @endforelse
        {{ $notifications->links() }}
    </section>
</x-layouts.app>
