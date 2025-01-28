<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum BoolActiveInactiveEnum: int
{
    use Options, Names, _Options;

    case Active = 1;

    case Inactive = 0;
}