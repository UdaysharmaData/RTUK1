<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum PagesListOrderByFieldsEnum: string
{
    use Options, Values, _Options;

    case Name = 'name';

    case Url = 'url';

    case CreatedAt = 'created_at';
}
