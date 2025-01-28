<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventCharitiesEnum: string
{
    use Options, Names, _Options;

    case All = 'all';

    case Included = 'included';

    case Excluded = 'excluded';
}