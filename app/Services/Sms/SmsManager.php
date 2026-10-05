<?php

namespace App\Services\Sms;

use App\Services\SettingService;
use InvalidArgumentException;

/**
 * Chooses the SMS provider from SMS_DRIVER and turns local phone numbers into
 * the international format that providers require.
 */
class SmsManager
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    public function gateway(): SmsGateway
    {
        return match (config('services.sms.driver')) {
            'twilio' => new TwilioGateway,
            'africastalking' => new AfricasTalkingGateway,
            'log' => new LogGateway,
            default => throw new InvalidArgumentException('Unknown SMS driver. Use twilio, africastalking or log.'),
        };
    }

    public function send(string $to, string $message): void
    {
        $this->gateway()->send($to, $message);
    }

    /**
     * Converts 0999 123 456, 999123456 or +265999123456 to +265999123456.
     * Returns null when the number cannot be a mobile number.
     */
    public function normalise(?string $phone): ?string
    {
        $code = preg_replace('/\D/', '', (string) $this->settings->get('phone_country_code', '265'));
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '+')) {
            $digits = substr($digits, 1);
        } elseif (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = $code.substr($digits, 1);
        } elseif (! str_starts_with($digits, $code)) {
            $digits = $code.$digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? '+'.$digits : null;
    }
}
