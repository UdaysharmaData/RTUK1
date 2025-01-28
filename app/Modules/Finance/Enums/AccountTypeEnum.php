<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum AccountTypeEnum: string
{
    use Options, Names, _Options;

    case Finite = 'finite';

    case Infinite = 'infinite';
}