<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum InvoiceItemStatusEnum: string
{
    use Options, Names, _Options;
 
    case Paid = 'paid';

    case Unpaid = 'unpaid';

    case Refunded = 'refunded';

    case Transferred = 'transferred';
}