<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum SiteUserActionEnum: string
{
    use Options, Values, _Options;

    case Restrict = 'restrict';

    case Unrestrict = 'unrestrict';
}
