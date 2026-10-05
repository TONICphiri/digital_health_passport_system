<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records who did what, and when, for accountability. A failure to write the
 * audit entry is logged but never stops the user's action.
 */
class AuditLogger
{
    public function record(string $action, string $description, ?Model $subject = null): void
    {
        try {
            $user = auth()->user();

            AuditLog::create([
                'user_id' => $user?->id,
                'facility_id' => $user?->facility_id,
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'ip_address' => request()?->ip(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Audit entry could not be saved.', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
