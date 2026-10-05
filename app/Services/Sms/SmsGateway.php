<?php

namespace App\Services\Sms;

/**
 * A provider that can deliver one text message. Implementations throw an
 * exception when the provider refuses the message.
 */
interface SmsGateway
{
    public function send(string $to, string $message): void;
}
