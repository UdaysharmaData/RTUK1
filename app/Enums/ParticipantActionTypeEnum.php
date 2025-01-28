<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantActionTypeEnum: string
{
    use Options, Names, _Options;

    case Added = 'added';

    case Deleted = 'deleted';

    case Restored = 'restored';

    case Transferred = 'transferred';
}