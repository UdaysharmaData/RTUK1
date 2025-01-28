<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum PartnerChannelsListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case Code = 'code';

    case CreatedAt = 'created_at';
}
