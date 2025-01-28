<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum LocationUseAsEnum: string
{
    use Options, Names, _Options;

    case Address = 'address';

    // case Route = 'route';  // NOTE: Kept for furture use. This represent the event route info and it gets plotted from a series of coordinates (lat & lng)
}