<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum InvoiceStateEnum: string
{
    use Options, Names, _Options;

    case Complete = 'complete';

    case Partial = 'partial';

    case Processing = 'processing';
}