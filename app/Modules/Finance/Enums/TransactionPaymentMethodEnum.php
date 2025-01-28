<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum TransactionPaymentMethodEnum: string
{
    use Options, Names, _Options;

    case Wallet = 'wallet';

    case Card = 'card';

    case Paypal = 'paypal';

    case ApplePay = 'apple_pay';

    case GooglePay = 'google_pay';

    case BacsDebit = 'bacs_debit';

    case Link = 'link';
}