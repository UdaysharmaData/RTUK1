<?php

namespace App\Enums;

use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum TimeReferenceEnum: string
{
    use Options, _Options, Values;

    case OneHour = '1h';

    case SixHours = '6h';

    case TwelveHours = '12h';

    case TwentyFourHours = '24h';

    case SevenDays = '7d';

    case ThirtyDays = '30d';

    case NinetyDays = '90d';

    case OneEightyDays = '180d';

    case OneYear = '1y';

    case All = 'All';
}
