<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum InvoiceTypeRefEnum: string
{
    use Options, Names, _Options;

    case INV_REG_ = 'participant_registration';

    case INV_PTR_ = 'participant_transfer';

    case INV_PTR_FEE = 'participant_transfer_fee';

    case INV_MKT_ = 'market_resale';

    case INV_MEM_ = 'charity_membership';

    case INV_PP_ = 'partner_package_assignment';

    case INV_EP_ = 'event_places';

    case INV_CC_ = 'corporate_credit';

    case INV_ = 'none';
}