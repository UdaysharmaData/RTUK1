<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ListingPageCharityTypeEnum: string
{
    use Options, Names, _Options;

    case PrimaryPartner = 'primary_partner';

    case SecondaryPartner = 'secondary_partner';
}