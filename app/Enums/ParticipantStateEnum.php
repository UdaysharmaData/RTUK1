<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantStateEnum: string
{
    use Options, Names, _Options;

    case PartiallyRegistered = 'partially_registered';

    case FullyRegistered = 'fully_registered';
}