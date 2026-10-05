<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The result of sending one reminder through one channel.
 * Channel is portal, email or sms. Status is sent, failed or skipped.
 */
class ReminderDelivery extends Model
{
    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const SKIPPED = 'skipped';

    protected $fillable = ['reminder_id', 'batch', 'channel', 'status', 'detail'];

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'portal' => 'Portal',
            'email' => 'Email',
            'sms' => 'Text message',
            default => ucfirst($this->channel),
        };
    }

    public function tone(): string
    {
        return match ($this->status) {
            self::SENT => 'success',
            self::FAILED => 'danger',
            default => 'neutral',
        };
    }
}
