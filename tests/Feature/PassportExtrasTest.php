<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\ReminderDelivery;
use App\Models\User;
use App\Models\Vaccination;
use App\Notifications\BackupNotification;
use App\Services\BackupService;
use App\Services\ReminderService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Holder access log, printable summary, vaccination certificate, reminder
 * preferences, delivery status, offline scan support and backups.
 */
class PassportExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function holder(): User
    {
        return User::query()->where('email', 'patient@healthpassport.mw')->firstOrFail();
    }

    private function admin(): User
    {
        return User::query()->role('system_admin')->firstOrFail();
    }

    private function worker(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function childVaccination(): Vaccination
    {
        return Vaccination::query()->where('patient_id', $this->holder()->patient->children()->first()->id)->firstOrFail();
    }

    /* Access log */

    public function test_the_holder_sees_the_full_access_log_and_others_do_not(): void
    {
        $this->actingAs($this->holder())->get(route('portal.access-log'))->assertOk()->assertSee('Who opened my passport');
        $this->actingAs($this->worker())->get(route('portal.access-log'))->assertForbidden();
    }

    public function test_the_access_log_of_a_child_is_limited_to_the_mother(): void
    {
        $child = $this->holder()->patient->children()->first();
        $this->actingAs($this->holder())->get(route('portal.access-log', ['patient' => $child->id]))->assertOk();

        $stranger = Patient::query()->where('national_id', 'PL2Q8X55')->firstOrFail();
        $this->actingAs($this->holder())->get(route('portal.access-log', ['patient' => $stranger->id]))->assertForbidden();
    }

    /* Summary */

    public function test_the_printable_summary_has_vaccinations_but_no_visit_notes(): void
    {
        $child = $this->holder()->patient->children()->first();

        $this->actingAs($this->holder())->get(route('portal.summary', ['patient' => $child->id]))
            ->assertOk()->assertSee($child->passport_number)->assertSee('BCG');

        $this->actingAs($this->holder())->get(route('portal.summary'))
            ->assertOk()->assertDontSee('Postnatal review');
    }

    /* Certificate */

    public function test_a_certificate_can_be_printed_by_the_mother_and_checked_by_anyone(): void
    {
        $vaccination = $this->childVaccination();

        $this->actingAs($this->holder())->get(route('certificates.show', $vaccination))
            ->assertOk()->assertSee('Vaccination certificate')->assertSee('<svg', false);

        auth()->logout();
        $this->flushSession();

        $this->get(URL::signedRoute('certificates.verify', $vaccination))
            ->assertOk()->assertSee('Genuine certificate')->assertSee($vaccination->vaccine->name)
            ->assertDontSee($vaccination->patient->last_name);

        $this->get(route('certificates.verify', $vaccination))->assertForbidden();
    }

    public function test_a_certificate_of_someone_elses_child_is_refused(): void
    {
        $stranger = User::create([
            'name' => 'Another Holder', 'email' => 'other@example.com', 'password' => 'Password@2026',
            'status' => \App\Enums\UserStatus::Active,
        ]);
        $stranger->assignRole('patient');

        $this->actingAs($stranger)->get(route('certificates.show', $this->childVaccination()))->assertForbidden();
    }

    /* Preferences */

    public function test_the_holder_saves_preferences_and_text_messages_need_a_phone(): void
    {
        $holder = $this->holder();

        $this->actingAs($holder)->get(route('portal.preferences.edit'))->assertOk();

        $this->actingAs($holder)->put(route('portal.preferences.update'), [
            'email' => '0', 'sms' => '1', 'categories' => ['vaccination' => '1', 'medication' => '0', 'follow_up' => '1'],
        ])->assertSessionHasNoErrors();

        $holder->refresh();
        $this->assertFalse($holder->notification_preferences['email']);
        $this->assertFalse($holder->notification_preferences['categories']['medication']);
        $this->assertTrue($holder->patient->fresh()->sms_consent);

        $holder->update(['phone' => null]);
        $this->actingAs($holder)->put(route('portal.preferences.update'), ['sms' => '1'])->assertSessionHasErrors('sms');
    }

    public function test_a_muted_reminder_type_is_not_sent_by_text_message_but_stays_in_the_portal(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.twilio' => ['sid' => 'AC1', 'token' => 't', 'from' => '+1', 'messaging_service_sid' => null]]);
        Http::fake();
        app(SettingService::class)->update(['sms_notifications_enabled' => '1']);

        $holder = $this->holder();
        $holder->update(['phone' => '0999123456', 'notification_preferences' => ['email' => true, 'categories' => ['vaccination' => false, 'medication' => false, 'follow_up' => false]]]);
        $holder->patient->update(['sms_consent' => true]);
        Reminder::query()->where('patient_id', $holder->patient_id)->update(['due_on' => today(), 'last_sent_at' => null]);

        app(ReminderService::class)->sendDue();

        Http::assertNothingSent();
        $this->assertGreaterThanOrEqual(1, $holder->notifications()->count());
    }

    /* Delivery status */

    public function test_delivery_status_is_recorded_per_channel_and_failures_are_visible(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.twilio' => ['sid' => 'AC1', 'token' => 't', 'from' => '+1', 'messaging_service_sid' => null]]);
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'unreachable'], 400)]);
        app(SettingService::class)->update(['sms_notifications_enabled' => '1']);

        $holder = $this->holder();
        $holder->update(['phone' => '0999123456']);
        $holder->patient->update(['sms_consent' => true]);
        $reminder = Reminder::query()->where('patient_id', $holder->patient_id)->firstOrFail();
        $reminder->update(['due_on' => today(), 'last_sent_at' => null]);

        app(ReminderService::class)->sendDue();

        $rows = ReminderDelivery::query()->where('reminder_id', $reminder->id)->pluck('status', 'channel');
        $this->assertSame('sent', $rows['portal']);
        $this->assertSame('failed', $rows['sms']);
        $this->assertCount(2, $rows);
    }

    public function test_a_reminder_with_nobody_to_reach_is_recorded_as_skipped(): void
    {
        $holder = $this->holder();
        $holder->update(['status' => 'inactive']);
        $reminder = Reminder::query()->where('patient_id', $holder->patient_id)->firstOrFail();
        $reminder->update(['due_on' => today(), 'last_sent_at' => null]);

        app(ReminderService::class)->sendDue();

        $this->assertSame('skipped', ReminderDelivery::query()->where('reminder_id', $reminder->id)->value('status'));
    }

    /* Offline scanning */

    public function test_the_scan_page_supports_keeping_a_card_while_offline(): void
    {
        $this->actingAs($this->worker())->get(route('patients.scan'))
            ->assertOk()
            ->assertSee('data-keep-minutes="30"', false)
            ->assertSee('data-worker=', false)
            ->assertSee('id="pending-scan"', false);

        $this->assertFileExists(public_path('sw.js'));
    }

    /* Backups */

    private function fakeStorage(): void
    {
        Storage::fake('local');
    }

    public function test_the_administrator_makes_a_backup_is_notified_and_can_download_it(): void
    {
        $this->fakeStorage();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');

        $backup = Backup::query()->firstOrFail();
        $this->assertTrue($backup->isCompleted());
        Storage::disk('local')->assertExists('backups/'.$backup->filename);

        // The file never contains readable patient data.
        $contents = Storage::disk('local')->get('backups/'.$backup->filename);
        $this->assertStringNotContainsString('Grace', $contents);
        $this->assertStringNotContainsString('KT7Y4M21', $contents);

        $this->assertSame(1, $admin->notifications()->where('type', BackupNotification::class)->count());

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee($backup->filename);
        $this->actingAs($admin)->get(route('admin.backups.download', $backup))->assertOk()->assertDownload($backup->filename);
    }

    public function test_only_the_system_administrator_can_reach_backups(): void
    {
        $this->fakeStorage();
        $backup = app(BackupService::class)->run('manual', $this->admin());

        foreach (['facility@healthpassport.mw', 'healthworker@healthpassport.mw', 'patient@healthpassport.mw'] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->actingAs($user)->get(route('admin.backups.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.backups.download', $backup))->assertForbidden();
            $this->actingAs($user)->post(route('admin.backups.store'))->assertForbidden();
        }
    }

    public function test_a_backup_restores_the_data_it_was_made_from(): void
    {
        $this->fakeStorage();
        $service = app(BackupService::class);
        $backup = $service->run('manual', $this->admin());
        $before = Patient::query()->count();

        Patient::query()->where('national_id', 'PL2Q8X55')->first()->forceDelete();
        $this->assertSame($before - 1, Patient::query()->count());

        $service->restore(Storage::disk('local')->path('backups/'.$backup->filename));

        $this->assertSame($before, Patient::query()->count());
        $this->assertNotNull(Patient::query()->where('national_id', 'PL2Q8X55')->first());
        $this->assertTrue(Backup::query()->whereKey($backup->id)->exists(), 'The backup history is kept.');
    }

    public function test_a_cut_off_backup_file_is_refused(): void
    {
        $this->fakeStorage();
        $service = app(BackupService::class);
        $backup = $service->run('manual', $this->admin());
        $path = Storage::disk('local')->path('backups/'.$backup->filename);

        $lines = file($path);
        file_put_contents($path, implode('', array_slice($lines, 0, -1)));
        $count = Patient::query()->count();

        $this->expectExceptionMessage('incomplete');
        try {
            $service->restore($path);
        } finally {
            $this->assertSame($count, Patient::query()->count(), 'Nothing is changed by a bad file.');
        }
    }

    public function test_old_backups_are_removed_but_the_newest_three_are_kept(): void
    {
        $this->fakeStorage();
        $service = app(BackupService::class);
        app(SettingService::class)->update(['backup_retention_days' => '7']);

        $old = collect(range(1, 5))->map(function ($i) use ($service) {
            $backup = $service->run('scheduled');
            $backup->forceFill(['created_at' => now()->subDays(20 + $i)])->save();

            return $backup;
        });

        $fresh = $service->run('scheduled');

        $this->assertTrue(Backup::query()->whereKey($fresh->id)->exists());
        $this->assertLessThan(6, Backup::query()->count());
        $this->assertGreaterThanOrEqual(3, Backup::query()->where('status', 'completed')->count());
    }
}
