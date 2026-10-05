<?php

namespace App\Http\Controllers;

use App\Enums\FacilityStatus;
use App\Enums\ReminderStatus;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shows each user the dashboard for their role. Every figure is read from
 * the database and limited to the user's own facility.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, SystemHealthService $health): View
    {
        $user = $request->user();

        return match ($user->role()) {
            RoleName::SystemAdmin => $this->systemAdmin($health),
            RoleName::FacilityAdmin => $this->facilityAdmin($user),
            RoleName::HealthWorker => $this->healthWorker($user),
            RoleName::Patient => $this->patient($user),
            default => abort(403, 'Your account does not have a role. Please contact your administrator.'),
        };
    }

    private function systemAdmin(SystemHealthService $health): View
    {
        return view('dashboards.system-admin', [
            'stats' => [
                'facilities' => Facility::query()->where('status', FacilityStatus::Active)->count(),
                'facilityAdmins' => User::query()->withRole(RoleName::FacilityAdmin)->count(),
                'staff' => User::query()->withRole(RoleName::HealthWorker)->count(),
                'patients' => Patient::query()->count(),
            ],
            'facilities' => Facility::query()->with('district')->withCount(['users', 'patients'])->latest()->limit(6)->get(),
            'checks' => $health->checks(),
            'activity' => AuditLog::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }

    private function facilityAdmin(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.facility-admin', [
            'stats' => [
                'staff' => User::query()->where('facility_id', $facilityId)->withRole(RoleName::HealthWorker)->active()->count(),
                'openedToday' => PassportAccess::query()->where('facility_id', $facilityId)->whereDate('opened_at', today())->count(),
                'encountersToday' => Encounter::query()->where('facility_id', $facilityId)->whereDate('recorded_at', today())->count(),
                'issuedThisMonth' => Patient::query()->where('registered_facility_id', $facilityId)->where('created_at', '>=', today()->startOfMonth())->count(),
            ],
            'staff' => User::query()->where('facility_id', $facilityId)->withRole(RoleName::HealthWorker)->orderBy('name')->limit(6)->get(),
            'activity' => AuditLog::query()->with('user')->where('facility_id', $facilityId)->latest()->limit(8)->get(),
        ]);
    }

    private function healthWorker(User $user): View
    {
        $facilityId = $user->facility_id;

        return view('dashboards.health-worker', [
            'stats' => [
                'openedToday' => PassportAccess::query()->where('user_id', $user->id)->whereDate('opened_at', today())->count(),
                'encountersToday' => Encounter::query()->where('recorded_by', $user->id)->whereDate('recorded_at', today())->count(),
                'issuedToday' => Patient::query()->where('registered_facility_id', $facilityId)->whereDate('created_at', today())->count(),
            ],
            'openPassports' => PassportAccess::query()->with('patient')->where('user_id', $user->id)->open()->latest('opened_at')->get(),
            'recentEncounters' => Encounter::query()->with('patient')->where('recorded_by', $user->id)->latest('recorded_at')->limit(8)->get(),
        ]);
    }

    private function patient(User $user): View
    {
        $patient = $user->patient;

        abort_if(! $patient, 403, 'This account is not linked to a health passport. Please contact the facility that issued it.');

        $patient->load(['children', 'registeredFacility']);
        $familyIds = $patient->children->pluck('id')->push($patient->id);

        return view('dashboards.patient', [
            'patient' => $patient,
            'reminders' => Reminder::query()->with('patient')->whereIn('patient_id', $familyIds)
                ->where('status', ReminderStatus::Active)->orderBy('due_on')->limit(5)->get(),
            'recentEncounters' => $patient->encounters()->with('facility')->limit(5)->get(),
            'recentAccesses' => $patient->accesses()->with(['user', 'facility'])->limit(5)->get(),
        ]);
    }
}
