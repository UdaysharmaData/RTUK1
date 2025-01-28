<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum CurrencyEnum: string
{
    use Options, Names, _Options, Values;

    case GBP = '£';

    case Euro = '€';

    case Usd = '$';

    case Cents = 'cent';
}
