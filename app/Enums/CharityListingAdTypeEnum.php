<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityListingAdTypeEnum: string
{
    use Options, Names, _Options;

    case Image = 'image';

    case Video = 'video';
}