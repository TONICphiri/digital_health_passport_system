<?php

namespace App\Enums;

/**
 * Every permission in the system. Roles are granted a subset of these in
 * RoleName::permissions(), and the seeder stores them in the database.
 */
enum Permission: string
{
    use HasOptions;

    // Platform administration (System Administrator)
    case ManageFacilities = 'facilities.manage';
    case ManageFacilityAdministrators = 'facility-administrators.manage';
    case ManageSystemSettings = 'system-settings.manage';
    case ViewSystemHealth = 'system-health.view';
    case ManageVaccineCatalogue = 'vaccine-catalogue.manage';
    case ViewAuditLogs = 'audit-logs.view';
    case ManageBackups = 'backups.manage';

    // Facility administration (Facility Administrator)
    case ManageStaff = 'staff.manage';
    case ManageFacilityProfile = 'facility-profile.manage';
    case ViewFacilityReports = 'facility-reports.view';

    // Point of care (Health Worker). Scanning the card is how a passport is opened.
    case OpenPassports = 'passports.open';
    case RegisterPatients = 'patients.register';
    case EditPatientDemographics = 'patients.edit-demographics';
    case RecordEncounters = 'encounters.record';
    case RecordVaccinations = 'vaccinations.record';
    case ManageReminders = 'reminders.manage';

    // Passport holder (Patient)
    case UsePatientPortal = 'portal.use';

    public function label(): string
    {
        return match ($this) {
            self::ManageFacilities => 'Register and manage facilities',
            self::ManageFacilityAdministrators => 'Manage facility administrator accounts',
            self::ManageSystemSettings => 'Change system settings',
            self::ViewSystemHealth => 'View system health',
            self::ManageVaccineCatalogue => 'Manage the vaccine list',
            self::ViewAuditLogs => 'View the activity log',
            self::ManageBackups => 'Make, download and delete backups',
            self::ManageStaff => 'Register and manage health workers',
            self::ManageFacilityProfile => 'Update the facility profile',
            self::ViewFacilityReports => 'View facility reports',
            self::OpenPassports => 'Open a passport by scanning the card',
            self::RegisterPatients => 'Issue new passports',
            self::EditPatientDemographics => 'Edit personal details and emergency contacts',
            self::RecordEncounters => 'Record an encounter in the passport',
            self::RecordVaccinations => 'Record vaccinations',
            self::ManageReminders => 'Set reminders',
            self::UsePatientPortal => 'Use the patient portal',
        };
    }
}
