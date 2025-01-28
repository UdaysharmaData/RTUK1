<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum EventsListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case StartDate = 'start_date';

    case EndDate = 'end_date';

    case CreatedAt = 'created_at';
}
