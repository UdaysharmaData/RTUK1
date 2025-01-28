<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum EventCategoriesListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case Visibility = 'visibility';

    case CreatedAt = 'created_at';
}
