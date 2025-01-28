<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum InternalTransactionsListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case ValidFrom = 'valid_from';

    case ValidTo = 'valid_to';

    case CreatedAt = 'created_at';
}