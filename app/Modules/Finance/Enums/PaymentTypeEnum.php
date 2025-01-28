<?php

namespace App\Modules\Finance\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PaymentTypeEnum: string
{
    use Options, Names, _Options;

    case ParticipantRegistration = 'participant_registration';

    case ParticipantTransfer = 'participant_transfer';
}