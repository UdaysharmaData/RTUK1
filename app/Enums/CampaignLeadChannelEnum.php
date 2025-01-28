<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CampaignLeadChannelEnum: string
{
    use Options, Names, _Options;

    case RunThroughMax = "runthroughmax";

    case LetsDoThisBespoke = "letsdothis";

    case LetsDoThisOwnPlace = "letsdothisown";

    case LetsDoThisRFC = "letsdothisrfc";

    case LetsDoThisFlagShip = "letsdothisflagship";
}