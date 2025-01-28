<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ResaleRequestStateEnum: string
{
    use Options, Names, _Options;

    case Pending = 'pending';

    case Accepted = 'accepted';

    case Paid = 'paid';

    case Cancelled = 'cancelled';
}