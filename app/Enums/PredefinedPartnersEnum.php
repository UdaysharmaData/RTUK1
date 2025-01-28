<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PredefinedPartnersEnum: string
{
    use Options, Names, _Options;

    case LetsDoThis = 'lets-do-this';
}
