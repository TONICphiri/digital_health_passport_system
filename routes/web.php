<?php

use App\Enums\Permission as P;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Clinical;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Facility;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patients;
use App\Http\Controllers\Portal;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Each group is protected by the permission it needs. Permission values come
| from App\Enums\Permission, which is also the source for the role matrix.
*/

$can = fn (P ...$permissions) => 'permission:'.implode('|', array_map(fn (P $p) => $p->value, $permissions));

Route::redirect('/', '/login');

// Public check of a printed vaccination certificate. The address is signed.
Route::get('verify/vaccination/{vaccination}', [Clinical\CertificateController::class, 'verify'])
    ->middleware(['signed', 'throttle:60,1'])->name('certificates.verify');

Route::middleware('guest')->group(function () {
    Route::get('login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('login', [Auth\LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [Auth\PasswordResetController::class, 'update'])->name('password.store');
});

Route::middleware('auth')->group(function () use ($can) {
    Route::post('logout', [Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('change-password', [Auth\ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('change-password', [Auth\ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::match(['get', 'post'], 'notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    /* System Administrator */
    Route::prefix('admin')->name('admin.')->group(function () use ($can) {
        Route::middleware($can(P::ManageFacilities))->group(function () {
            Route::resource('facilities', Admin\FacilityController::class)->except('destroy');
            Route::patch('facilities/{facility}/status', [Admin\FacilityController::class, 'toggleStatus'])->name('facilities.status');
        });
        Route::middleware($can(P::ManageFacilityAdministrators))->group(function () {
            Route::resource('facility-administrators', Admin\FacilityAdministratorController::class)
                ->parameters(['facility-administrators' => 'user'])->except(['show', 'destroy']);
            Route::patch('facility-administrators/{user}/status', [Admin\FacilityAdministratorController::class, 'toggleStatus'])->name('facility-administrators.status');
            Route::post('facility-administrators/{user}/reset-password', [Admin\FacilityAdministratorController::class, 'resetPassword'])->name('facility-administrators.reset-password');
        });
        Route::middleware($can(P::ManageSystemSettings))->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::resource('districts', Admin\DistrictController::class)->except(['show', 'destroy']);
        });
        Route::middleware($can(P::ManageBackups))->prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [Admin\BackupController::class, 'index'])->name('index');
            Route::post('/', [Admin\BackupController::class, 'store'])->name('store');
            Route::get('{backup}/download', [Admin\BackupController::class, 'download'])->name('download');
            Route::delete('{backup}', [Admin\BackupController::class, 'destroy'])->name('destroy');
        });
        Route::get('system-health', Admin\SystemHealthController::class)->middleware($can(P::ViewSystemHealth))->name('system-health');
        Route::resource('vaccines', Admin\VaccineController::class)->except(['show', 'destroy'])->middleware($can(P::ManageVaccineCatalogue));
    });

    Route::get('audit-log', [Admin\AuditLogController::class, 'index'])->middleware($can(P::ViewAuditLogs))->name('audit-log.index');

    /* Facility Administrator */
    Route::prefix('facility')->name('facility.')->group(function () use ($can) {
        Route::middleware($can(P::ManageStaff))->group(function () {
            Route::resource('staff', Facility\StaffController::class)->parameters(['staff' => 'user'])->except(['show', 'destroy']);
            Route::patch('staff/{user}/status', [Facility\StaffController::class, 'toggleStatus'])->name('staff.status');
            Route::post('staff/{user}/reset-password', [Facility\StaffController::class, 'resetPassword'])->name('staff.reset-password');
        });
        Route::middleware($can(P::ManageFacilityProfile))->group(function () {
            Route::get('profile', [Facility\FacilityProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Facility\FacilityProfileController::class, 'update'])->name('profile.update');
        });
        Route::get('reports', Facility\ReportController::class)->middleware($can(P::ViewFacilityReports))->name('reports');
    });

    /* Health Worker. Every visit starts by scanning the card. */
    Route::middleware($can(P::OpenPassports))->group(function () {
        Route::get('passports/open', [Patients\AccessController::class, 'create'])->name('patients.scan');
        Route::post('passports/open', [Patients\AccessController::class, 'store'])->middleware('throttle:passport-open')->name('patients.open');
        Route::post('passports/sync', [Patients\OfflineVisitController::class, 'store'])->middleware('throttle:passport-open')->name('patients.sync');
        Route::get('offline/scan', [Patients\OfflineVisitController::class, 'shell'])->name('offline.scan');
        Route::delete('passports/access/{access}', [Patients\AccessController::class, 'destroy'])->name('patients.close');
    });
    Route::middleware($can(P::RegisterPatients))->group(function () {
        Route::get('patients/create', [Patients\PatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [Patients\PatientController::class, 'store'])->name('patients.store');
        Route::post('patients/{patient}/portal-account', [Patients\PatientController::class, 'createPortalAccount'])->name('patients.portal-account');
    });
    Route::get('patients/{patient}', [Patients\PatientController::class, 'show'])->name('patients.show');
    Route::get('vaccinations/{vaccination}/certificate', [Clinical\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('patients/{patient}/card', [Patients\PatientController::class, 'card'])->name('patients.card');
    Route::middleware($can(P::EditPatientDemographics))->group(function () {
        Route::get('patients/{patient}/edit', [Patients\PatientController::class, 'edit'])->name('patients.edit');
        Route::put('patients/{patient}', [Patients\PatientController::class, 'update'])->name('patients.update');
    });
    Route::middleware($can(P::RecordEncounters))->group(function () {
        Route::get('patients/{patient}/encounters/create', [Clinical\EncounterController::class, 'create'])->name('encounters.create');
        Route::post('patients/{patient}/encounters', [Clinical\EncounterController::class, 'store'])->name('encounters.store');
    });
    Route::middleware($can(P::RecordVaccinations))->group(function () {
        Route::get('patients/{patient}/vaccinations/create', [Clinical\VaccinationController::class, 'create'])->name('vaccinations.create');
        Route::post('patients/{patient}/vaccinations', [Clinical\VaccinationController::class, 'store'])->name('vaccinations.store');
    });
    Route::middleware($can(P::ManageReminders))->group(function () {
        Route::get('patients/{patient}/reminders/create', [Clinical\ReminderController::class, 'create'])->name('reminders.create');
        Route::post('patients/{patient}/reminders', [Clinical\ReminderController::class, 'store'])->name('reminders.store');
        Route::patch('reminders/{reminder}/stop', [Clinical\ReminderController::class, 'stop'])->name('reminders.stop');
    });

    /* Patient portal */
    Route::prefix('my')->name('portal.')->middleware($can(P::UsePatientPortal))->group(function () {
        Route::get('records', [Portal\RecordController::class, 'index'])->name('records');
        Route::get('card', [Portal\RecordController::class, 'card'])->name('card');
        Route::get('access-log', [Portal\RecordController::class, 'accessLog'])->name('access-log');
        Route::get('summary', [Portal\RecordController::class, 'summary'])->name('summary');
        Route::get('preferences', [Portal\PreferenceController::class, 'edit'])->name('preferences.edit');
        Route::put('preferences', [Portal\PreferenceController::class, 'update'])->name('preferences.update');
    });
});
