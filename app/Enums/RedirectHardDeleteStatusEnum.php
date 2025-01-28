<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum RedirectHardDeleteStatusEnum: string
{
    use Options, Names, _Options;

    case Temporal = 'temporal';

    case Permanent = 'permanent';
}
