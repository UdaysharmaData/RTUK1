<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum ListSoftDeletedItemsOptionsEnum: string
{
    use Options, Values, _Options;

    case With = 'with';

    case Without = 'without';

    case Only = 'only';
}
