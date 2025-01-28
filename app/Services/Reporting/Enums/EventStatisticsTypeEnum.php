<?php

namespace App\Services\Reporting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum EventStatisticsTypeEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;

    case Invoices = 'invoices';

    case Events = 'events';

    case Participants = 'participants';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [EventStatisticsTypeEnum::Invoices]
        ];
    }
}