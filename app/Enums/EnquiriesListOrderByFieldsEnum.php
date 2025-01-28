<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum EnquiriesListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case FullName = 'full_name';

    case FirstName = 'first_name';

    case LastName = 'last_name';

    case Email = 'email';

    case Gender = 'gender';

    case CreatedAt = 'created_at';
}
