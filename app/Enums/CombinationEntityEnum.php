<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum CombinationEntityEnum: string
{
    use Values, _Options;

    case Categories = 'categories';

    case Regions = 'regions';

    case Cities = 'cities';

    case Venues = 'venues';
}
