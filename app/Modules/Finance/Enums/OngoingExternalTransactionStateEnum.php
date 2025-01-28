<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum OngoingExternalTransactionStateEnum: string
{
    use Options, Names, _Options;

    case Failed = 'failed';

    case Completed = 'completed';
}