<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends through the Africa's Talking SMS API. No SDK is needed.
 * Set AFRICASTALKING_ENV=sandbox while testing.
 */
class AfricasTalkingGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        $config = config('services.africastalking');

        if (empty($config['username']) || empty($config['key'])) {
            throw new RuntimeException("Africa's Talking is not configured. Check the AFRICASTALKING_ values in .env.");
        }

        $host = $config['environment'] === 'sandbox' ? 'api.sandbox.africastalking.com' : 'api.africastalking.com';

        $payload = ['username' => $config['username'], 'to' => $to, 'message' => $message];

        if (! empty($config['from'])) {
            $payload['from'] = $config['from'];
        }

        $response = Http::asForm()
            ->withHeaders(['apiKey' => $config['key'], 'Accept' => 'application/json'])
            ->timeout((int) config('services.sms.timeout'))
            ->post("https://{$host}/version1/messaging", $payload);

        if ($response->failed()) {
            throw new RuntimeException("Africa's Talking refused the message: ".$response->status());
        }

        $status = $response->json('SMSMessageData.Recipients.0.status');

        if ($status !== null && $status !== 'Success') {
            throw new RuntimeException("Africa's Talking could not deliver the message: {$status}");
        }
    }
}
