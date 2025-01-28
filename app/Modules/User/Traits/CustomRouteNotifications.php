<?php

namespace App\Modules\User\Traits;

trait CustomRouteNotifications
{
    /**
     * Identifier to send sms notification
     * @param $notifiable
     * @return mixed
     */
    public function routeNotificationForSms($notifiable): mixed
    {
        return $this->phone;
    }
}
