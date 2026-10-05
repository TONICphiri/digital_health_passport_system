<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Collects the technical status shown on the System Health page.
 */
class SystemHealthService
{
    /**
     * @return array<int, array{label: string, value: string, healthy: bool}>
     */
    public function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->storageCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            [
                'label' => 'Application environment',
                'value' => ucfirst(app()->environment()).(config('app.debug') ? ', debug mode on' : ''),
                'healthy' => ! (app()->isProduction() && config('app.debug')),
            ],
            ['label' => 'PHP version', 'value' => PHP_VERSION, 'healthy' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['label' => 'Laravel version', 'value' => app()->version(), 'healthy' => true],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        return [
            'facilities' => Facility::query()->count(),
            'users' => User::query()->count(),
            'patients' => Patient::query()->count(),
        ];
    }

    private function databaseCheck(): array
    {
        try {
            $database = DB::connection()->getDatabaseName();
            $bytes = (int) DB::table('information_schema.tables')
                ->where('table_schema', $database)
                ->sum(DB::raw('data_length + index_length'));

            return [
                'label' => 'Database connection',
                'value' => "Connected to {$database}, ".number_format($bytes / 1048576, 2).' MB used',
                'healthy' => true,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['label' => 'Database connection', 'value' => 'Not reachable', 'healthy' => false];
        }
    }

    private function storageCheck(): array
    {
        $writable = is_writable(storage_path('logs')) && is_writable(storage_path('framework'));

        return [
            'label' => 'File storage',
            'value' => $writable ? 'Writable' : 'Not writable. Check folder permissions for storage.',
            'healthy' => $writable,
        ];
    }

    private function queueCheck(): array
    {
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;

        return [
            'label' => 'Background jobs waiting',
            'value' => (string) $pending,
            'healthy' => $pending < 100,
        ];
    }

    private function failedJobsCheck(): array
    {
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'label' => 'Failed background jobs',
            'value' => (string) $failed,
            'healthy' => $failed === 0,
        ];
    }
}
