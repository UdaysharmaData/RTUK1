<?php

namespace App\NotifcationDrivers;

use App\Services\TwoFactorAuth\Twilio;
use Illuminate\Notifications\Notification;

class SmsDriver
{

    /**
     * @param $notifiable
     * @param Notification $notification
     * @return void
     * @throws \Exception
     */
    public function send($notifiable, Notification $notification)
    {
        $phone = method_exists($notifiable, 'routeNotificationForSms') ? $notifiable->routeNotificationForSms($notifiable) : null;
        $message = method_exists($notification, 'toSms') ? $notification->toSms($notifiable) : null;

        // TODO SETUP TWILIO TO SENT SMS
        if ($phone) {
            (new Twilio())->sendSms($phone, $message);
        };
    }
}
