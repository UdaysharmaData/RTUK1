<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PromotionalPageRegionEnum: string
{
    use Options, Names, _Options;

    case EastOfEngland = 'east_of_england'; // Should be renamed to east. Fru said

    case Ireland = 'ireland';

    case London = 'london';

    case West = 'west';

    case South = 'south';

    case SouthEast = 'south_east';

    case Midlands = 'midlands';

    case NorthEast = 'north_east';

    case NorthWest = 'north_west';

    case Scotland = 'scotland';

    case SouthWest = 'south_west';

    case Wales = 'wales';

    case Yorkshire = 'yorkshire';

    case Overseas = 'overseas';

    case Virtual = 'virtual';
}