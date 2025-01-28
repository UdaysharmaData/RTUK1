<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum PageStatus: int
{
    use Options, Values, _Options;

    case Online = 1;

    case Offline = 0;
}
