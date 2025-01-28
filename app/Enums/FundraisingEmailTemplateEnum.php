<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum FundraisingEmailTemplateEnum: string
{
    use Options, Names, _Options;

    case Drip1 = 'drip1';

    case Dri2 = 'drip2';

    case Drip3 = 'drip3';

    case Drip4 = 'drip4';
}