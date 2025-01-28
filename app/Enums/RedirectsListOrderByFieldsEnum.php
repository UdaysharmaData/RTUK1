<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum RedirectsListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case CreatedAt = 'created_at';
}
