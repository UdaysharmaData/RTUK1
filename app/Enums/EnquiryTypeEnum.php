<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EnquiryTypeEnum: string
{
    use Options, Names, _Options;

    case General = 'General';

    case Press = 'Press';

    case RaceEntriesNorth = 'Race Entries in the North';

    case RaceEntriesMidlands = 'Race Entries in the Midlands';

    case PartnershipAndSponsorship = 'Partnership & Sponsorship Opportunities';

    case Volunteering = 'Volunteer Opportunities';
}
