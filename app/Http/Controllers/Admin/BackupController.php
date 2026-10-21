<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Lets the System Administrator see, make and download backups.
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups, private readonly AuditLogger $audit)
    {
    }

    public function index(SettingService $settings): View
    {
        return view('admin.backups.index', [
            'backups' => Backup::query()->with('createdBy')->latest('id')->paginate($this->perPage()),
            'automatic' => $settings->get('backup_enabled', '1') === '1',
            'time' => config('health_passport.backup.time'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        set_time_limit(0);

        $backup = $this->backups->run('manual', $request->user());
        $this->audit->record('backup.created', 'Made a backup of the database.', $backup);

        return $backup->isCompleted()
            ? back()->with('success', 'The backup has been saved.')
            : back()->with('error', 'The backup could not be completed. '.$backup->error);
    }

    public function download(Backup $backup): StreamedResponse
    {
        abort_unless($backup->isCompleted() && Storage::disk(config('health_passport.backup.disk'))->exists($this->backups->path($backup)), 404);

        $this->audit->record('backup.downloaded', "Downloaded the backup {$backup->filename}.", $backup);

        return Storage::disk(config('health_passport.backup.disk'))->download($this->backups->path($backup), $backup->filename);
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->audit->record('backup.deleted', "Deleted the backup {$backup->filename}.", $backup);
        $this->backups->delete($backup);

        return back()->with('success', 'The backup has been deleted.');
    }

    /**
     * The route already requires the backups.manage permission, so the form
     * and the restore itself are covered by that middleware.
     */
    public function restoreForm(): View
    {
        return view('admin.backups.restore');
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file'],
            'confirm' => ['required', 'accepted'],
        ]);

        set_time_limit(0);

        $path = $request->file('backup_file')->getRealPath();

        try {
            $restored = $this->backups->restore($path);
            $this->audit->record('backup.restored', 'Restored the database from an uploaded backup.', ['restored' => $restored]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The backup could not be restored. '.$exception->getMessage());
        }

        return redirect()->route('admin.backups.index')->with('success', 'The backup was restored successfully.');
    }
}
