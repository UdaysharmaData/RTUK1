<?php

namespace App\Modules\Event\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventRegistrationMethodTypesEnum: string
{
    use Options, Names, _Options;

    case Internal = 'internal';

    case External = 'external';
}