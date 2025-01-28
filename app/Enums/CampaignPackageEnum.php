<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CampaignPackageEnum: string
{
    use Options, Names, _Options;

    case Leads_25 = '25_leads';

    case Leads_50 = '50_leads';

    case Leads_100 = '100_leads';

    case Leads_250 = '250_leads';

    case Leads_500 = '500_leads';

    case Leads_1000 = '1000_leads';

    case Leads_2500 = '2500_leads';

    case Leads_5000 = '5000_leads';

    case Classic = 'classic';

    case Premium = 'premium';

    case Year_2 = '2 year';
}