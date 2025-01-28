<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventPlaceInvoicePeriodInMonthRangeTextEnum: string
{
    use Options, Names, _Options;

    case MarchMay = '03_05';

    case JuneAugust = '06_08';

    case SeptemberNovember = '09_11';

    case DecemberFebruary = '12_02';
}