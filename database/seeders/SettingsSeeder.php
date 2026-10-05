<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

/**
 * Default system settings. Existing values are never overwritten, so the
 * seeder is safe to run again after a System Administrator has made changes.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['system_name', 'Digital Health Passport', 'System name', 'general', 'text', null],
            ['country_name', 'Republic of Malawi', 'Country name shown on the card and sign in page', 'general', 'text', null],
            ['issuing_authority', 'Ministry of Health', 'Issuing authority', 'general', 'text', null],
            ['passport_number_prefix', 'MW', 'Passport number prefix', 'general', 'text', null],
            ['national_id_length', '8', 'National ID length', 'general', 'number', null],
            ['child_separation_age', '18', 'Age at which a child passport becomes independent', 'general', 'number', null],
            ['passport_session_minutes', '30', 'Minutes a scanned passport stays open', 'general', 'number', null],
            ['offline_draft_hours', '24', 'Hours a visit typed offline is kept on the device', 'general', 'number', null],
            ['email_notifications_enabled', '0', 'Send notifications by email', 'notifications', 'boolean', null],
            ['sms_notifications_enabled', '0', 'Send notifications by text message', 'notifications', 'boolean', null],
            ['phone_country_code', '265', 'Country calling code for phone numbers', 'notifications', 'text', null],
            ['backup_enabled', '1', 'Make a backup every day', 'backups', 'boolean', null],
            ['backup_retention_days', '30', 'Days to keep backups', 'backups', 'number', null],
            ['facility_types', "Central Hospital\nDistrict Hospital\nCommunity Hospital\nHealth Centre\nClinic\nDispensary", 'Facility types', 'lists', 'list', null],
            ['ownership_types', "Government\nChristian Health Association of Malawi\nPrivate\nNon governmental organisation", 'Ownership types', 'lists', 'list', null],
            ['relationship_types', "Mother\nFather\nSpouse\nSon\nDaughter\nBrother\nSister\nGuardian\nFriend\nOther", 'Emergency contact relationships', 'lists', 'list', null],
            ['dosage_frequencies', "Once daily\nTwice daily\nThree times daily\nFour times daily\nEvery 8 hours\nAt night\nWhen required", 'Medicine frequencies', 'lists', 'list', null],
            ['regions', "Northern\nCentral\nSouthern", 'Regions', 'lists', 'list', null],
        ];

        foreach ($settings as [$key, $value, $label, $group, $inputType, $helpText]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'value' => $value,
                'label' => $label,
                'group' => $group,
                'input_type' => $inputType,
                'help_text' => $helpText,
            ]);
        }

        app(SettingService::class)->flush();
    }
}
