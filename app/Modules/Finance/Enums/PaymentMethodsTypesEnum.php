<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PaymentMethodsTypesEnum: string
{
    use Options, Names, _Options;

    case NewPaymentMethod = 'new_payment_method';

    case SavedPaymentMethods = 'saved_payment_methods';
}