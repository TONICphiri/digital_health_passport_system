<x-layouts.app title="Backups">
    <x-page-header title="Backups">
        <x-slot:breadcrumb>{{ $automatic ? 'Automatic backup every day at '.$time : 'Automatic backups are switched off' }}</x-slot:breadcrumb>
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button type="submit" class="btn-primary"><x-icon name="database" class="h-4 w-4" /> Back up now</button></form>
        </x-slot:actions>
    </x-page-header>

    <section class="panel">
        @if ($backups->isEmpty())
            <x-empty title="No backups yet" icon="database" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Made</th><th>File</th><th>Size</th><th>How</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td class="whitespace-nowrap">{{ $backup->created_at->format('j M Y, H:i') }}</td>
                                <td class="mono">{{ $backup->filename ?? 'Not saved' }}</td>
                                <td class="tabular-nums">{{ $backup->isCompleted() ? $backup->readableSize() : 'Not available' }}</td>
                                <td>{{ $backup->trigger === 'scheduled' ? 'Automatic' : 'By '.($backup->createdBy?->name ?? 'an administrator') }}</td>
                                <td>
                                    @if ($backup->isCompleted())
                                        <x-badge tone="success">Completed</x-badge>
                                    @else
                                        <x-badge tone="danger" title="{{ $backup->error }}">Failed</x-badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    @if ($backup->isCompleted())
                                        <a href="{{ route('admin.backups.download', $backup) }}" class="link text-sm">Download</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}" class="ml-3 inline" onsubmit="return confirm('Delete this backup?')">@csrf @method('DELETE')<button class="link text-sm text-red-700">Delete</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $backups->links() }}
        @endif
    </section>
</x-layouts.app>
