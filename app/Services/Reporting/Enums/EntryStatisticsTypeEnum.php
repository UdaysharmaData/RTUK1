<?php

namespace App\Services\Reporting\Enums;

use App\Modules\Setting\Enums\SiteEnum;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EntryStatisticsTypeEnum: string
{
    use Options, Names, _Options;

    case Entries = 'entries';

    case Invoices = 'invoices';
}