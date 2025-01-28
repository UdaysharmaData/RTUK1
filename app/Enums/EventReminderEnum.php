<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventReminderEnum: string
{
    use Options, Names, _Options;
 
    case Daily = 'daily';

    case Weekly = 'weekly';

    case Monthly = 'monthly';
}