<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum PartnersListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case Code = 'code';

    case Expiry = 'expiry';

    case CreatedAt = 'created_at';
}
