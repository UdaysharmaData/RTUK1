<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventPlaceInvoicePeriodEnum: string
{
    use Options, Names, _Options;

    case 03_05 = '03_05';

    case 06_08 = '06_08';

    case 09_11 = '09_11';

    case 12_02 = '12_02';
}