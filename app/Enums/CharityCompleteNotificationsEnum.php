<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityCompleteNotificationsEnum: string
{
    use Options, Names, _Options;

    case Always = 'always';

    case Weekly = 'weekly';

    case Monthly = 'monthly';
}