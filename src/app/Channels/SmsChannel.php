<?php

namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $message = $notification->toSms($notifiable);
        $phone = $notifiable->routeNotificationFor('sms');

        if (app()->environment('production')) {
            // Здесь будет отправка SMS через API-шлюз
            $sms = new SmsService();
            $sms->send($phone, $message);
        } else {
            Log::info("FAKE SMS to $phone: $message");
        }
    }
}
