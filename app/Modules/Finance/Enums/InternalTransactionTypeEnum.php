<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum InternalTransactionTypeEnum: string
{
    use Options, Names, _Options;

    case Credit = 'credit';

    case Debit = 'debit';
}