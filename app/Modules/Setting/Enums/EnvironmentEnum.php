<?php

namespace App\Modules\Setting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EnvironmentEnum: string
{
    use Options, Names, _Options;

    case Api = 'api';

    case Portal = 'portal';

    case Website = 'website';
}