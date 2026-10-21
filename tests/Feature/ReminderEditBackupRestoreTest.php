<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Editing a reminder that was already set, and restoring a backup from a file
 * the administrator uploads, rather than from the list of saved backups.
 */
class ReminderEditBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_a_worker_can_open_and_save_the_edit_form_of_a_reminder(): void
    {
        $worker = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $patient = Patient::query()->where('national_id', 'PL2Q8X55')->firstOrFail();

        $this->actingAs($worker)->post(route('patients.open'), ['code' => 'DHP:'.$patient->qr_token])->assertRedirect();

        $reminder = Reminder::query()->create([
            'patient_id' => $patient->id,
            'facility_id' => $patient->facility_id,
            'category' => 'follow_up',
            'title' => 'Review at the clinic',
            'message' => 'Come back for a review.',
            'due_on' => now()->addWeek(),
            'status' => 'active',
            'created_by' => $worker->id,
        ]);

        $this->actingAs($worker)->get(route('reminders.edit', $reminder))->assertOk()->assertSee('Edit reminder');

        $this->actingAs($worker)->put(route('reminders.update', $reminder), [
            'category' => 'medication',
            'title' => 'Collect monthly medication',
            'message' => 'Come back on the first Monday of the month.',
            'due_on' => now()->addWeek()->toDateString(),
            'repeat_every_days' => 30,
            'is_confidential' => '1',
        ])->assertRedirect(route('patients.show', $patient));

        $reminder->refresh();

        $this->assertSame('Collect monthly medication', $reminder->title);
        $this->assertSame(30, $reminder->repeat_every_days);
        $this->assertTrue($reminder->is_confidential);
    }

    public function test_the_administrator_can_restore_a_backup_from_an_uploaded_file(): void
    {
        Storage::fake('local');

        $admin = User::query()->role('system_admin')->firstOrFail();
        $backup = app(BackupService::class)->run('manual', $admin);

        $path = Storage::disk('local')->path('backups/'.$backup->filename);
        $upload = new UploadedFile($path, 'upload.dhpbak', null, null, true);

        $this->actingAs($admin)->get(route('admin.backups.restore'))->assertOk()->assertSee('Restore');

        // A successful restore must not be reported as a failure, so the page
        // has to come back with a success message, not an error.
        $this->actingAs($admin)->post(route('admin.backups.restore.store'), [
            'backup_file' => $upload,
            'confirm' => 'on',
        ])->assertRedirect(route('admin.backups.index'))->assertSessionHas('success');

        $this->assertTrue(Backup::query()->whereKey($backup->id)->exists());
    }

    public function test_a_health_worker_cannot_open_the_restore_form(): void
    {
        $worker = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();

        $this->actingAs($worker)->get(route('admin.backups.restore'))->assertForbidden();
    }
}
