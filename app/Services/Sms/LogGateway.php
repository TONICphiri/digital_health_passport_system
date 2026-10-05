<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Writes the message to the log instead of sending it. Used while developing.
 */
class LogGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS (not sent, log driver)', ['to' => $to, 'message' => $message]);
    }
}
