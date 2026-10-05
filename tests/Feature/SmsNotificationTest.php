<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use App\Services\SettingService;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_phone_numbers_are_converted_to_international_format(): void
    {
        $sms = app(SmsManager::class);

        $this->assertSame('+265999123456', $sms->normalise('0999 123 456'));
        $this->assertSame('+265999123456', $sms->normalise('+265 999 123 456'));
        $this->assertSame('+265999123456', $sms->normalise('999123456'));
        $this->assertNull($sms->normalise('12'));
        $this->assertNull($sms->normalise(null));
    }

    private function dueReminderFor(User $account): Reminder
    {
        $reminder = Reminder::query()->where('patient_id', $account->patient_id)->firstOrFail();
        $reminder->update(['due_on' => today(), 'last_sent_at' => null, 'is_confidential' => true]);

        return $reminder;
    }

    private function portalAccount(): User
    {
        return User::query()->where('email', 'patient@healthpassport.mw')->firstOrFail();
    }

    public function test_a_reminder_is_sent_by_text_message_when_the_holder_agreed(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.twilio' => ['sid' => 'ACtest', 'token' => 't', 'from' => '+15005550006', 'messaging_service_sid' => null]]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        $account = $this->portalAccount();
        $account->update(['phone' => '0999123456']);
        $account->patient->update(['sms_consent' => true]);
        app(SettingService::class)->update(['sms_notifications_enabled' => '1']);
        $this->dueReminderFor($account);

        app(ReminderService::class)->sendDue();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'Accounts/ACtest/Messages.json')
            && $request['To'] === '+265999123456'
            && ! str_contains($request['Body'], 'medication'));
    }

    public function test_no_text_message_is_sent_without_consent(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.twilio' => ['sid' => 'ACtest', 'token' => 't', 'from' => '+15005550006', 'messaging_service_sid' => null]]);
        Http::fake();

        $account = $this->portalAccount();
        $account->update(['phone' => '0999123456']);
        $account->patient->update(['sms_consent' => false]);
        app(SettingService::class)->update(['sms_notifications_enabled' => '1']);
        $this->dueReminderFor($account);

        app(ReminderService::class)->sendDue();

        Http::assertNothingSent();
    }

    public function test_a_provider_failure_does_not_stop_the_reminder_run(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.twilio' => ['sid' => 'ACtest', 'token' => 't', 'from' => '+15005550006', 'messaging_service_sid' => null]]);
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'bad'], 400)]);

        $account = $this->portalAccount();
        $account->update(['phone' => '0999123456']);
        $account->patient->update(['sms_consent' => true]);
        app(SettingService::class)->update(['sms_notifications_enabled' => '1']);
        $this->dueReminderFor($account);

        $this->assertGreaterThanOrEqual(1, app(ReminderService::class)->sendDue());
        $this->assertGreaterThanOrEqual(1, $account->notifications()->count());
    }
}
