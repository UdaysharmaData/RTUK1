<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum MetaRobotsEnum: string
{
    use Options, Values, _Options;

    case Index = 'index';

    case NoIndex = 'noindex';

    case Follow = 'follow';

    case NoFollow = 'nofollow';
}
