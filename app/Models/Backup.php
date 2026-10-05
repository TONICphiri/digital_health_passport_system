<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    protected $fillable = ['filename', 'size_bytes', 'status', 'trigger', 'error', 'created_by'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The size in words a person reads. Written by hand because the framework's
     * own helper needs the "intl" PHP extension, which many servers leave off.
     */
    public function readableSize(): string
    {
        $bytes = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $bytes : number_format($bytes, 1, '.', '')).' '.$units[$i];
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }
}
