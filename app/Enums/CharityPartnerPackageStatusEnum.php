<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CharityPartnerPackageStatusEnum: string
{
    use Options, Names, _Options;

    case Assigned = 'assigned';

    case Paid = 'paid';
}