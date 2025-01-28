<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum RedirectTypeEnum: string
{
    use Options, Names, _Options;

    case Single = 'single';

    case Collection = 'collection';
}
