<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum AccountStatusEnum: string
{
    use Options, Names, _Options;
    
    case Active = 'active';

    case Inactive = 'inactive';
}