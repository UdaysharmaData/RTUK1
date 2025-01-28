<?php

namespace App\Services\Auth\Enums;

enum NotificationType : string
{
    case Email = 'mail';
    case Phone = 'sms';
}
