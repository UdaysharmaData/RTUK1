<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CampaignStatusEnum: string
{
    use Options, Names, _Options;

    case Created = 'created';

    case Active = 'active';

    case Complete = 'complete';
}