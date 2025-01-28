<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum FundraisingEmailScheduleTypeEnum: string
{
    use Options, Names, _Options;

    case Before = 'before';

    case After = 'after';
}