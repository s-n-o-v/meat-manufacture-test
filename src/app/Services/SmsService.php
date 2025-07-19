<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    protected string $apiId;

    public function __construct()
    {
        $this->apiId = config('services.smsru.key');
    }

    public function send(string $phone, string $message): bool
    {
        $response = Http::get('https://sms.ru/sms/send', [
            'api_id' => $this->apiId,
            'to' => $phone,
            'msg' => $message,
            'json' => 1,
        ]);

        return $response->json('status') === 'OK';
    }
}
