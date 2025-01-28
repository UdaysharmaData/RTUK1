<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventPagePaymentOptionEnum: string
{
    use Options, Names, _Options;

    case Participant = 'participant';

    case Charity = 'charity';

    // case Corporate = 'corporate';
}