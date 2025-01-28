<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum BoolEnabledDisabledEnum: int
{
    use Options, Names, _Options;

    case Enabled = 1;

    case Disabled = 0;
}