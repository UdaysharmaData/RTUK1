<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum InvoicesListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case Type = 'type';

    case Status = 'status';

    case Price = 'price';

    case CreatedAt = 'created_at';

}