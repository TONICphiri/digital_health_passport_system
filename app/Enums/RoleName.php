<?php

namespace App\Enums;

/**
 * The four user roles in the system.
 *
 *  - System Administrator: runs the national platform, never sees patient records.
 *  - Facility Administrator: manages the health workers of one facility.
 *  - Health Worker: opens a passport by scanning the card and adds to it.
 *  - Patient: the passport holder, who reads and carries their own passport.
 */
enum RoleName: string
{
    use HasOptions;

    case SystemAdmin = 'system_admin';
    case FacilityAdmin = 'facility_admin';
    case HealthWorker = 'health_worker';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Administrator',
            self::FacilityAdmin => 'Facility Administrator',
            self::HealthWorker => 'Health Worker',
            self::Patient => 'Patient',
        };
    }

    public function tone(): string
    {
        return 'neutral';
    }

    /**
     * Roles a Facility Administrator may create for their own facility.
     *
     * @return array<int, self>
     */
    public static function facilityStaffRoles(): array
    {
        return [self::HealthWorker];
    }

    /**
     * Roles that belong to a facility and must have a facility assigned.
     */
    public function belongsToFacility(): bool
    {
        return in_array($this, [self::FacilityAdmin, self::HealthWorker], true);
    }

    /**
     * The permissions granted to this role. This is the single source of
     * truth for role based access and is written to the database by the seeder.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SystemAdmin => [
                Permission::ManageFacilities,
                Permission::ManageFacilityAdministrators,
                Permission::ManageSystemSettings,
                Permission::ViewSystemHealth,
                Permission::ManageVaccineCatalogue,
                Permission::ViewAuditLogs,
                Permission::ManageBackups,
            ],
            self::FacilityAdmin => [
                Permission::ManageStaff,
                Permission::ManageFacilityProfile,
                Permission::ViewFacilityReports,
                Permission::ViewAuditLogs,
            ],
            self::HealthWorker => [
                Permission::OpenPassports,
                Permission::RegisterPatients,
                Permission::EditPatientDemographics,
                Permission::RecordEncounters,
                Permission::RecordVaccinations,
                Permission::ManageReminders,
            ],
            self::Patient => [
                Permission::UsePatientPortal,
            ],
        };
    }
}
