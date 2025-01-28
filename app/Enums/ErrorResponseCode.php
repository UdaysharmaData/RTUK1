<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;

enum ErrorResponseCode: string
{
    use Options, Names, _Options;

    case TwoFactorRequired = '2FA_REQUIRED';

    case InvalidTwoFactorToken = 'INVALID_2FA_TOKEN';

    case PaymentRequired = 'PAYMENT_REQUIRED';

}
