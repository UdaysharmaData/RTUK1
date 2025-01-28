<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ContractStateEnum: string
{
    use Options, Names, _Options;

    case Current = 'current';

    case Archived = 'archived';
}