<?php

namespace App\Services\Reporting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Modules\Setting\Enums\OrganisationCodeEnum;
use App\Contracts\Enums\OrganisationExcludes;

enum DashboardStatisticsTypeEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;

    case Events = 'events';

    case Entries = 'entries';

    case Invoices = 'invoices';

    case Participants = 'participants';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [DashboardStatisticsTypeEnum::Invoices]
        ];
    }
}