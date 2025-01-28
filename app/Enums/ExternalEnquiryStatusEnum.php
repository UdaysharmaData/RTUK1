<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ExternalEnquiryStatusEnum: string
{
    use Options, Names, _Options;

    case Pending = 'pending';

    case Processed = 'processed';
}