<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityMembershipTypeEnum: string
{
    use Options, Names, _Options;

    case Classic = 'classic';

    case Partner = 'partner';

    case Premium = 'premium';

    case TwoYear = 'two_year';
}