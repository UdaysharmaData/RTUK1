<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum ParticipantProfileWeeklyPhysicalActivityEnum: string
{
    use Options, Names, _Options, Values;

    case Days_1_2 = '1 - 2 days';

    case Days_3_5 = '3 - 5 days';

    case Days_6_Plus = '6+ days';
}
