<x-layouts.app title="Restore a backup">
    <x-page-header title="Restore a backup">
        <x-slot:breadcrumb><a href="{{ route('admin.backups.index') }}" class="hover:text-brand-700">Backups</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Restore</x-slot:breadcrumb>
    </x-page-header>

    <div class="panel max-w-2xl">
        <div class="panel-body space-y-5">
            <p class="text-sm text-muted">
                Restoring replaces everything in the database with the contents of the backup file. Every passport,
                visit, vaccination and setting is erased first, so the file must be one this system made and the
                database must already be at the same structure.
            </p>

            <div>
                <label for="backup_file" class="label">Backup file <span class="text-red-700">*</span></label>
                <input id="backup_file" name="backup_file" type="file" accept=".bak,.gz,.json,application/gzip,application/json"
                    class="input" required>
                @error('backup_file')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <x-field.checkbox name="confirm" label="I understand that restoring replaces the current data"
                help="This cannot be undone. Make a backup of the current database first if you are unsure." />
            @error('confirm')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('admin.backups.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" form="restore-backup-form">Restore from backup</button>
        </div>
    </div>

    <form id="restore-backup-form" method="POST" action="{{ route('admin.backups.restore.store') }}" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>

    <script>
        document.getElementById('restore-backup-form').addEventListener('submit', function () {
            if (! window.confirm('Replace everything in the database with this backup? This cannot be undone.')) {
                event.preventDefault();
            }
        });
    </script>
</x-layouts.app>
