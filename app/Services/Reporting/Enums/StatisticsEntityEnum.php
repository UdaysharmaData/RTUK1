<?php

namespace App\Services\Reporting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum StatisticsEntityEnum: string
{
    use Options, Names, _Options;

    case Participant = 'participant';

    case Event = 'event';

    case Charity = 'charity';

    case Invoice = 'invoice';

    case Dashboard = 'dashboard';

    case User = 'user';

    case Region = 'region';

    case City = 'city';

    case Venue = 'venue';

    case EventCategory = 'event_category';

    case ExternalEnquiry = 'external_enquiry';

    case Enquiry = 'enquiry';

    case Partner = 'partner';

    case Combination = 'combination';

    case Entry = 'entry';

    case Experience = 'experience';

    case Medal = 'medal';

    case Sponsor = 'sponsor';

    case Serie = 'serie';

    case Audience = 'audience';
}
