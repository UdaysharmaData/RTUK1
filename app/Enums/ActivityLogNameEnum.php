<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ActivityLogNameEnum: string
{
    use Options, Names, _Options;

    case Created = 'created';

    case Updated = 'updated';

    case Deleted = 'deleted';

    case Restored = 'restored';

    case Transferring = 'transferring';

    case Transferred = 'transferred';
}