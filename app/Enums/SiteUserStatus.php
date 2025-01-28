<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;

enum SiteUserStatus: string
{
    use Options, Names, _Options;

    case Active = 'active';

    case Restricted = 'restricted';
}
