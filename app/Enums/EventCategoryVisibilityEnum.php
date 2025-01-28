<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventCategoryVisibilityEnum: string
{
    use Options, Names, _Options;

    case Public = 'public';

    case Private = 'private';
}