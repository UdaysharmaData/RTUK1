<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum MonthEnum: int
{
    use Options, Names, _Options, Values;

    case Jan = 1;

    case Feb = 2;

    case Mar = 3;

    case Apr = 4;

    case May = 5;

    case Jun = 6;

    case Jul = 7;

    case Aug = 8;

    case Sep = 9;

    case Oct = 10;

    case Nov = 11;

    case Dec = 12;
}
