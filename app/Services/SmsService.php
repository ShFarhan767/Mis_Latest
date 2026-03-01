<?php

namespace App\Services;

use App\Models\SmsApiInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS using the first active sms_api_infos record.
     *
     * @param  string  $to   E.164 or the gateway expected format
     * @param  string  $message
     * @return array { success: bool, response: mixed }
     */
    public function send(string $to, string $message): array
    {
        $api = SmsApiInfo::where('status', 0)->first(); // adjust query if 'status' meaning differs

        if (! $api) {
            return [
                'success' => false,
                'message' => 'No SMS API configured',
            ];
        }

        $url = rtrim($api->smsLinkUrl, '/');

        // Build payload. **Different gateways require different keys**.
        // Using the typical keys: username, password, to, from, message
        // Adjust the keys below to match your provider (mobireach may expect other param names).
        $payload = [
            'username' => $api->userName,
            'password' => $api->password,
            'to' => $to,
            'from' => $api->from,
            'message' => $message,
        ];

        // If your gateway wants JSON body:
        try {
            $response = Http::asForm()->post($url, $payload);

            Log::info('SMS send attempt', [
                'url' => $url,
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->body()];
            }

            return ['success' => false, 'response' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('SMS send exception: '.$e->getMessage(), ['payload' => $payload]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
