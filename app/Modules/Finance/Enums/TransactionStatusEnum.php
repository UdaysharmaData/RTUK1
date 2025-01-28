<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum TransactionStatusEnum: string
{
    use Options, Names, _Options;
    
    case Pending = 'pending';

    case Processing = 'processing';

    case Failed = 'failed';

    case Completed = 'completed';
}