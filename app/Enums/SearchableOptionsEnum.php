<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum SearchableOptionsEnum: string
{
    use Options, Values, Names, _Options;

    case Recent = 'recent';

    case All = 'all';

    case Events = 'events';

    case Categories = 'categories';

    case Regions = 'regions';

    case Cities = 'cities';

    case Venues = 'venues';

    case Combinations = 'combinations';

    case Charities = 'charities';

    case Pages = 'pages';
}