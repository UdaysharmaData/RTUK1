<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantProfileTshirtSizeEnum: string
{
    use Options, Names, _Options;

    case XXXS = 'xxxs';

    case XXS = 'xxs';

    case XS = 'xs';

    case SM = 'sm';

    case M = 'm';

    case L = 'l';

    case XL = 'xl';

    case XXL = 'xxl';

    case XXXL = 'xxxl';
}
