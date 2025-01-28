<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantPaymentStatusEnum: string
{
    use Options, Names, _Options;
    
    case Unpaid = 'unpaid';

    case Paid = 'paid';

    case Waived = 'waived';

    case Refunded = 'refunded';

    case Transferred = 'transferred';
}