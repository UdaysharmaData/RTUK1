<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityListingAdPositionEnum: string
{
    use Options, Names, _Options;

    case Inline = 'inline';

    case Side = 'side';
}