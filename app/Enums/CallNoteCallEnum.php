<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CallNoteCallEnum: string
{
    use Options, Names, _Options;

    case Months_23 = '23_months';

    case Months_21 = '21_months';

    case Months_18 = '18_months';

    case Months_15 = '15_months';

    case Months_12 = '12_months';

    case Months_11 = '11_months';

    case Months_8 = '8_months';

    case Months_5 = '5_months';

    case Month_2 = '2_months';

    case Month_1 = '1_month';

    case All = 'all';

    // TODO: write a trait that convert the name above to this format below.

    // case 23_months = '23_months';

    // case 21_months = '21_months';

    // case 18_months = '18_months';

    // case 15_months = '15_months';

    // case 12_months = '12_months';

    // case 11_months = '11_months';

    // case 8_months = '8_months';

    // case 5_months = '5_months';

    // case 2_months = '2_months';

    // case 1_month = '1_month';
}