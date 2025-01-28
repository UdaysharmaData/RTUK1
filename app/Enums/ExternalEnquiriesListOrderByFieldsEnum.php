<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum ExternalEnquiriesListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case FirstName = 'first_name';

    case LastName = 'last_name';

    case FullName = 'full_name';

    case Email = 'email';

    case PartnerChannel = 'partner_channel';

    case CreatedAt = 'created_at';

    case LDTCreatedAt = 'ldt_created_at';
}
