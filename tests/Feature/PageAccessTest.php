<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens the pages of every role and checks that each role sees only its own work.
 */
class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** @return array<string, array<int, string>> */
    private function allowedPages(): array
    {
        return [
            'admin@healthpassport.mw' => ['dashboard', 'admin.facilities.index', 'admin.districts.index', 'admin.facility-administrators.index', 'admin.settings.edit', 'admin.system-health', 'admin.vaccines.index', 'admin.backups.index', 'audit-log.index'],
            'facility@healthpassport.mw' => ['dashboard', 'facility.staff.index', 'facility.staff.create', 'facility.profile.edit', 'facility.reports', 'audit-log.index'],
            'healthworker@healthpassport.mw' => ['dashboard', 'patients.scan', 'patients.create', 'notifications.index'],
            'patient@healthpassport.mw' => ['dashboard', 'portal.records', 'portal.card', 'portal.access-log', 'portal.preferences.edit', 'portal.summary', 'notifications.index'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function forbiddenPages(): array
    {
        return [
            'admin@healthpassport.mw' => ['patients.scan', 'patients.create', 'portal.records', 'facility.staff.index'],
            'facility@healthpassport.mw' => ['patients.scan', 'patients.create', 'admin.facilities.index', 'admin.settings.edit', 'admin.backups.index', 'portal.records'],
            'healthworker@healthpassport.mw' => ['facility.staff.index', 'facility.reports', 'admin.facilities.index', 'admin.backups.index', 'audit-log.index', 'portal.records', 'portal.preferences.edit'],
            'patient@healthpassport.mw' => ['patients.scan', 'patients.create', 'facility.staff.index', 'admin.facilities.index', 'audit-log.index'],
        ];
    }

    public function test_each_role_can_open_its_own_pages(): void
    {
        foreach ($this->allowedPages() as $email => $routes) {
            $user = User::query()->where('email', $email)->firstOrFail();

            foreach ($routes as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
        }
    }

    public function test_each_role_is_kept_out_of_other_roles_pages(): void
    {
        foreach ($this->forbiddenPages() as $email => $routes) {
            $user = User::query()->where('email', $email)->firstOrFail();

            foreach ($routes as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_hospital_management_pages_do_not_exist(): void
    {
        foreach (['campaigns.index', 'appointments.index', 'admissions.index', 'pharmacy.index', 'facility.wards.index', 'visits.queue', 'patients.index'] as $name) {
            $this->assertFalse(Route::has($name), "{$name} should not exist");
        }
    }

    public function test_there_are_only_four_roles(): void
    {
        $this->assertSame(
            ['facility_admin', 'health_worker', 'patient', 'system_admin'],
            \Spatie\Permission\Models\Role::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('patients.scan'))->assertRedirect(route('login'));
    }
}
