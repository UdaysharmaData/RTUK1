<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CacheTypeEnum: string
{
    use Options, Names, _Options;

    case Site = 'sites';

    case ApiClient = 'clients';
}
