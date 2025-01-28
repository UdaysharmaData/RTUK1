<?php

namespace App\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum ListTypeEnum: string
{
    use Options, Values, _Options;

    case Pages = 'Pages';

    case Combinations = 'Combinations';

    case Users = 'Users';

    case Events = 'Events';

    case Participants = 'Participants';

    case Entries = 'Entries';

    case Enquiries = 'Enquiries';

    case ExternalEnquiries = 'ExternalEnquiries';

    case Medals = 'Medals';

    case Partners = 'Partners';

    case PartnerChannels = 'PartnerChannels';

    case EventCategories = 'EventCategories';

    case Regions = 'Regions';

    case Invoices = 'Invoices';

    case EventPropertyServices = 'EventPropertyServices';

    case Experiences = 'Experiences';

    case Roles = 'Roles';

    case Redirects = 'Redirects';

    case InternalTransactions = 'InternalTransactions';

    case Audiences = 'Audiences';

    case Uploads = 'Uploads';
}
