<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum RedirectStatusEnum: string
{
    use Options, Names, _Options;

    case Temporal = 'temporal';

    case Permanent = 'permanent';
}
