<?php

/*
|--------------------------------------------------------------------------
| Digital Health Passport configuration
|--------------------------------------------------------------------------
|
| Fixed medical standards live here. Everything a System Administrator may
| need to change, such as the passport number prefix or the National ID
| length, is stored in the settings table and edited on the Settings page.
|
*/

return [

    'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],

    // Accepted range for each measurement, used by the form and its validation.
    'vital_limits' => [
        'temperature' => ['min' => 30, 'max' => 45],
        'weight' => ['min' => 0.3, 'max' => 400],
        'height' => ['min' => 20, 'max' => 250],
        'systolic_pressure' => ['min' => 50, 'max' => 260],
        'diastolic_pressure' => ['min' => 30, 'max' => 160],
        'pulse_rate' => ['min' => 20, 'max' => 250],
        'oxygen_saturation' => ['min' => 50, 'max' => 100],
    ],

    // Most medicines that can be noted in one encounter.
    'max_medications' => 10,

    // Seconds between checks for new notifications in the browser.
    'notification_poll_seconds' => (int) env('NOTIFICATION_POLL_SECONDS', 60),

    // Number of rows shown per page in lists.
    'per_page' => (int) env('LIST_PAGE_SIZE', 15),

    // Load demonstration facilities, staff and patients when seeding.
    // Set to false on the live server.
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', true),

    // Database backups. How long copies are kept is a setting; these are
    // technical values that belong to the server.
    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'directory' => 'backups',
        'time' => env('BACKUP_TIME', '02:00'),
        // Data that can be rebuilt, or that must not be restored.
        'skip_tables' => ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations', 'backups'],
        'chunk_rows' => 200,
    ],

    // The first System Administrator account created by the seeder.
    'admin' => [
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'email' => env('ADMIN_EMAIL', 'phiritonic550@gmail.com'),
        'password' => env('ADMIN_PASSWORD', 'Password@2026'),
    ],

];
