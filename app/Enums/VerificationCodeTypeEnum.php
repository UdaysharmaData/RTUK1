<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum VerificationCodeTypeEnum: string
{
    use Options, Names, _Options;

    case PasswordReset = 'password_reset';

    case AccountVerification = 'account_verification';

    case PasswordSetup = 'password_setup';
}
