<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum FaqCategoryNameEnum: string
{
    use Values, Names, Options, _Options;

    case General = 'general';

    case Events = 'events';

    case Payments = 'payments';

    case Orders = 'orders';
}
