<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum QueueNameEnum: string
{
    use Options, Values, Names, _Options;

    case Default = 'default';

    case High = 'high';

    case LdtOffer = 'ldtoffer';
    case LdtOfferSingleParticipant = 'ldtoffersingleparticipant';

}
