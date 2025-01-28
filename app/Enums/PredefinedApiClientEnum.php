<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PredefinedApiClientEnum: string
{
    use Options, Names, _Options;

    case RunForCharity = 'runforcharity.com';

    case RunThroughHub = 'hub.runthrough.co.uk';

    case RunThrough = 'runthrough.co.uk';

    case SportsMediaAgency = 'sportsmediaagency.com';
}