<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventCustomFieldRuleEnum: string
{
    use Options, Names, _Options;

    case Required = 'required';

    case Optional = 'optional';
}