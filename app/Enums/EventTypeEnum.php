<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventTypeEnum: string
{
    use Options, Names, _Options;

    case Standalone = 'standalone';

    case Rolling = 'rolling';
}