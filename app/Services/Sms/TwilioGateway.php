<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends through the Twilio Messages API. No SDK is needed.
 * Use TWILIO_MESSAGING_SERVICE_SID, or TWILIO_FROM (a number or approved sender ID).
 */
class TwilioGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        $config = config('services.twilio');

        if (empty($config['sid']) || empty($config['token']) || (empty($config['from']) && empty($config['messaging_service_sid']))) {
            throw new RuntimeException('Twilio is not configured. Check the TWILIO_ values in .env.');
        }

        $payload = ['To' => $to, 'Body' => $message];

        if (! empty($config['messaging_service_sid'])) {
            $payload['MessagingServiceSid'] = $config['messaging_service_sid'];
        } else {
            $payload['From'] = $config['from'];
        }

        $response = Http::asForm()
            ->withBasicAuth($config['sid'], $config['token'])
            ->timeout((int) config('services.sms.timeout'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['sid']}/Messages.json", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Twilio refused the message: '.($response->json('message') ?? $response->status()));
        }
    }
}
