<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;

enum UserVerificationStatus: string
{
    use Options, Names, _Options;

    case Verified = 'verified';

    case Unverified = 'unverified';
}
