<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventRouteInfoTypeEnum: string
{
    use Options, Names, _Options;

    case RouteImage = 'route_image';

    case EmbedCode = 'embed_code';
}
