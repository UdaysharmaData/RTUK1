<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum TransactionTypeEnum: string
{
    use Options, Names, _Options;
    
    case Deposit = 'deposit';

    case Withdrawal = 'withdrawal';

    case Allocation = 'allocation';

    case Payment = 'payment';

    case Refund = 'refund';

    case Transfer = 'transfer';
}