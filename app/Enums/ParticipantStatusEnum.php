<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantStatusEnum: string
{
    use Options, Names, _Options;

    case Complete = 'complete';

    case Notified = 'notified';

    case Clearance = 'clearance';

    case Incomplete = 'incomplete';

    case Transferred = 'transferred';
}