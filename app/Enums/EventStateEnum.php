<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventStateEnum: string
{
    use Options, Names, _Options;

    case Live = 'live';

    case Expired = 'expired';

    case Archived = 'archived';
}