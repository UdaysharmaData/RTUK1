<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityUserTypeEnum: string
{
    use Options, Names, _Options;

    case Owner = 'owner';

    case Manager = 'manager';

    case User = 'user';

    case Participant = 'participant';
}