<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ListingPageTypeEnum: string
{
    use Options, Names, _Options;

    case Creator = 'creator';

    case Wizard = 'wizard';
}