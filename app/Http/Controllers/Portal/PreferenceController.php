<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ReminderCategory;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Lets the passport holder choose how reminders reach them. The copy in the
 * portal is always kept; these choices only apply to email and text messages.
 */
class PreferenceController extends Controller
{
    public function edit(Request $request, SettingService $settings): View
    {
        $user = $request->user();
        $preferences = $user->notification_preferences ?? [];

        return view('portal.preferences', [
            'user' => $user,
            'email' => ($preferences['email'] ?? true) !== false,
            'sms' => (bool) $user->patient?->sms_consent,
            'categories' => collect(ReminderCategory::cases())->mapWithKeys(
                fn (ReminderCategory $category) => [$category->value => [
                    'label' => $category->label(),
                    'wanted' => ($preferences['categories'][$category->value] ?? true) !== false,
                ]]
            ),
            'emailAvailable' => $settings->get('email_notifications_enabled') === '1',
            'smsAvailable' => $settings->get('sms_notifications_enabled') === '1',
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['nullable', 'boolean'],
            'sms' => ['nullable', 'boolean'],
            'categories' => ['array'],
            'categories.*' => ['nullable', 'boolean'],
        ]);

        if (($data['sms'] ?? false) && blank($user->phone)) {
            throw ValidationException::withMessages(['sms' => 'Add your phone number on your profile before turning on text messages.']);
        }

        $user->update(['notification_preferences' => [
            'email' => (bool) ($data['email'] ?? false),
            'categories' => collect(ReminderCategory::cases())->mapWithKeys(
                fn (ReminderCategory $category) => [$category->value => (bool) ($data['categories'][$category->value] ?? false)]
            )->all(),
        ]]);

        $user->patient?->update(['sms_consent' => (bool) ($data['sms'] ?? false)]);

        $audit->record('portal.preferences-updated', 'Changed the reminder preferences.', $user->patient);

        return back()->with('success', 'Your preferences have been saved.');
    }
}
