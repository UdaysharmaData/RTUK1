<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantWaiveEnum: string
{
    use Options, Names, _Options;

    case Completely = 'completely';

    case Partially = 'partially';
}