<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum RegionEnum: string
{
    use Options, Names, _Options;

    case Ireland = 'ireland';

    case London = 'london';

    case West = 'west';

    case South = 'south';

    case EastOfEngland = 'east_of_england';

    case SouthWest = 'south_west';

    case SouthEast = 'south_east';

    case NorthWest = 'north_west';

    case NorthEast = 'north_east';

    case Midlands = 'midlands'; // REMOVE THIS

    case EastMidlands = 'east_midlands';

    case WestMidlands = 'west_midlands';

    case Scotland = 'scotland';

    case Wales = 'wales';

    case Yorkshire = 'yorkshire'; // REMOVE THIS

    case YorkshireAndTheHumber = 'yorkshire_and_the_humber';

    case Overseas = 'overseas';

    case Virtual = 'virtual';
}