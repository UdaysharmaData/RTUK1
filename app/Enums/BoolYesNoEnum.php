<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum BoolYesNoEnum: int
{
    use Options, Names, _Options;

    case Yes = 1;

    case No = 0;
}