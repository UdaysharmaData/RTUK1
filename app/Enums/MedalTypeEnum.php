<?php

namespace App\Enums;

use App\Contracts\Enums\SiteExcludes;
use App\Services\ClientOptions\Traits\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Names;

enum MedalTypeEnum: string
{
    use Options, Names, _Options;

    case Default = 'default';



}
