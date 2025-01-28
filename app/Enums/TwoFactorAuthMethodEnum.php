<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum TwoFactorAuthMethodEnum: string
{
    use Options, Names, _Options;

    case Sms2Fa = 'sms_2fa';

    case Email2Fa = 'email_2fa';

    case Google2Fa = 'google_2fa';
}
